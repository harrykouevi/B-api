<?php

namespace App\Listeners;

use App\Criteria\Purchases\PaidPurchasesCriteria;
use App\Criteria\Purchases\PurchasesByBookingCriteria;
use App\Criteria\Purchases\PurchasesOfUserCriteria;
use App\Events\DoPaymentEvent;
use App\Events\NotifyPaymentEvent;
use App\Events\NotifyBookingEvent;
use App\Models\User;
use App\Models\Booking;
use App\Models\Tax;
use App\Repositories\BookingRepository;
use App\Repositories\SalonRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Repositories\PurchaseRepository;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Repositories\TaxRepository;
use App\Types\WalletType;
use App\Models\PlatformRevenue;
use App\Types\PlatformRevenueType;

/**
 * Listener UpdateBookingPaymentListener
 *
 * Ce listener centralise et gère toutes les transactions financières
 * nécessaires lors d’un changement d’état d’une réservation (Booking).
 *
 * Les cas pris en charge :
 * - Annulation : remboursement client (selon qui annule : salon ou client).
 * - Report : prélèvement d’une commission (salon ou client).
 * - Acceptation : création/mise à jour d’un achat et traitement du paiement
 *   (Wallet ou Cash).
 *
 * En résumé, ce listener est le point unique où sont orchestrées
 * les logiques financières liées au cycle de vie d’un booking.
 */

class UpdateBookingPaymentListener
{
     /**
     * @var BookingRepository
     */
    private BookingRepository $bookingRepository;

     /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

     /**
     * @var WalletRepository
     */
    private WalletRepository $walletRepository;


    /**
     * @var TaxRepository
     */
    private TaxRepository $taxRepository;

     /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

    /**
     * @var PurchaseRepository
     */
    private PurchaseRepository $purchaseRepository;

    /**
     * @var WalletTransactionRepository
     */
    private WalletTransactionRepository $walletTransactionRepository;

    /**
     * Create the event listener.
     *
     * @param BookingRepository $bookingRepository
     */
    public function __construct(PaymentService $paymentService , BookingRepository $bookingRepository ,PurchaseRepository $purchaseRepository ,
        WalletTransactionRepository $walletTransactionRepository ,
     WalletRepository $walletRepository ,
     TaxRepository $taxRepository  , SalonRepository $salonRepository)
    {
        $this->bookingRepository = $bookingRepository ;
        $this->walletRepository = $walletRepository ;
        $this->taxRepository = $taxRepository ;
        $this->salonRepository = $salonRepository;
        $this->paymentService = $paymentService ;
        $this->purchaseRepository = $purchaseRepository ;
        $this->walletTransactionRepository = $walletTransactionRepository;



    }


    /**
     * Récupère le wallet utilisé pour payer une réservation donnée.
     *
     * - Vérifie si le paiement est effectué par Wallet et que son statut est différent de "3" (validé).
     * - Cherche la transaction associée dans le repository.
     * - Détermine si le wallet est de type BONUS ou PRINCIPAL.
     *
     * @param Booking $booking  Réservation concernée
     *
     * @return array|null  [
     *     'transaction' => WalletTransaction,   // Transaction trouvée
     *     'wallet_type' => string               // Type de wallet utilisé (BONUS|PRINCIPAL)
     * ] ou null si aucune transaction n'est trouvée
     */
    private function getWalletUseToPayBooking(Booking $booking): ?array
    {
        Log::info('UpdateBookingPaymentListener - getWalletUseToPayBooking start', [
            'booking_id' => $booking->id,
            'payment_id' => $booking->payment_id,
            'payment_method' => $booking->payment->paymentMethod->name ?? null,
        ]);

        // Vérifie si le paiement est avec le wallet
        if ($booking->payment && $booking->payment->paymentMethod->name === 'Wallet') {

            // Chercher la dernière transaction wallet du client pour cette réservation
            // On ne vérifie plus paymentStatus_id car le paiement des frais peut déjà être validé
            $walletTransaction = $this->walletTransactionRepository
                ->where('user_id', $booking->user_id)
                ->where('payment_id', $booking->payment_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($walletTransaction) {
                Log::info('UpdateBookingPaymentListener - transaction wallet trouvée', [
                    'wallet_transaction_id' => $walletTransaction->id,
                    'wallet_id' => $walletTransaction->wallet_id,
                    'wallet_type' => $walletTransaction->wallet->name ?? null,
                    'amount' => $walletTransaction->amount,
                ]);
            }

            // Si pas de transaction trouvée avec payment_id, chercher le wallet du client directement
            if (!$walletTransaction) {
                Log::info('UpdateBookingPaymentListener - Aucune transaction trouvée, récupération du wallet client directement', [
                    'user_id' => $booking->user_id,
                    'payment_id' => $booking->payment_id
                ]);

                // Récupérer les wallets du client (bonus d'abord, puis principal)
                $clientWallets = $this->walletRepository->findWhere([
                    'user_id' => $booking->user_id,
                    'enabled' => true
                ])->sortByDesc(function($wallet) {
                    return $wallet->name === WalletType::BONUS->value ? 1 : 0;
                });

                $wallet = null;
                $walletType = null;

                // Chercher un wallet avec solde suffisant
                $requiredAmount = $booking->getTotal();
                foreach ($clientWallets as $w) {
                    if ($w->balance >= $requiredAmount) {
                        $wallet = $w;
                        $walletType = ($w->name === WalletType::BONUS->value)
                            ? WalletType::BONUS
                            : WalletType::PRINCIPAL;
                        break;
                    }
                }

                // Si aucun wallet avec solde suffisant, prendre le principal
                if (!$wallet) {
                    $wallet = $clientWallets->firstWhere('name', WalletType::PRINCIPAL->value);
                    $walletType = WalletType::PRINCIPAL;
                }

                Log::info('UpdateBookingPaymentListener - wallet client sélectionné', [
                    'wallet_id' => $wallet?->id,
                    'wallet_type' => $walletType?->value,
                    'balance' => $wallet?->balance,
                    'required_amount' => $requiredAmount,
                ]);

                if (!$wallet) {
                    Log::error('UpdateBookingPaymentListener - Aucun wallet trouvé pour le client', [
                        'user_id' => $booking->user_id
                    ]);
                    return [ null , null];
                }

                return [$wallet, $walletType];
            }

            // Déterminer le type de wallet utilisé
            $walletType = $walletTransaction->wallet->name ?? null;

            $walletType = ($walletType === WalletType::BONUS->value)
                            ? WalletType::BONUS
                            : WalletType::PRINCIPAL;

            Log::info('UpdateBookingPaymentListener - wallet provenant de la transaction', [
                'wallet_id' => $walletTransaction->wallet->id ?? null,
                'wallet_type' => $walletType->value,
                'wallet_balance' => $walletTransaction->wallet->balance ?? null,
            ]);

            return [
                $walletTransaction->wallet,
                $walletType,
            ];
        }

        return [ null , null];
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            /** @var Booking $booking */
            $booking = $event->booking;
            Log::info('UpdateBookingPaymentListener - handle', [
                'booking_id' => $booking->id,
                'booking_status_id' => $booking->booking_status_id,
                'payment_id' => $booking->payment_id,
                'payment_status_id' => $booking->payment->paymentStatus_id ?? null,
                'payment_method' => $booking->payment->paymentMethod->name ?? null,
            ]);

            $payment_intents =[];

            if( in_array($booking->booking_status_id, [7, 8]) && $booking->payment->paymentStatus_id != 3){
                //si le statut de la reservation est failed et que le statut du paiement est tout sauf failed
                //faire le remboursement necessaire
                $purchaseamount = 0  ;
                $purchasepayment = Null ;

                [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;

                // Récupérer la pénalité d'annulation depuis les settings
                $cancellationCharge = setting('cancellation_charge', 20);

                Log::info('🚫 ANNULATION DE RÉSERVATION', [
                    'booking_id' => $booking->id,
                    'cancelled_by' => auth()->user()->hasRole('salon owner') ? 'SALON' : 'CLIENT',
                    'cancellation_charge' => $cancellationCharge
                ]);

                //si il y a eu achat trouver le montant de l'achat'
                // IMPORTANT: Si c'est le salon qui annule, chercher par booking_id uniquement
                // Si c'est le client qui annule, chercher par user_id ET booking_id
                if(auth()->user()->hasRole('salon owner')) {
                    // Salon annule : chercher le purchase du booking (appartient au client)
                    $this->purchaseRepository->pushCriteria(new PurchasesByBookingCriteria());
                    $this->purchaseRepository->pushCriteria(new PaidPurchasesCriteria());
                    $purchase = $this->purchaseRepository->get()->first(function ($purchase)  use ($booking) {
                        return $purchase->booking && $purchase->booking->id == $booking->id;
                    });
                } else {
                    // Client annule : chercher par user_id
                    $this->purchaseRepository->pushCriteria(new PurchasesOfUserCriteria(auth()->id()));
                    $this->purchaseRepository->pushCriteria(new PurchasesByBookingCriteria());
                    $this->purchaseRepository->pushCriteria(new PaidPurchasesCriteria());
                    $purchase = $this->purchaseRepository->get()->first(function ($purchase)  use ($booking) {
                        return $purchase->booking && $purchase->booking->id == $booking->id;
                    });
                }

                if($purchase) {

                    if($purchase->purchaseStatus->order == 50) $purchaseamount = $purchase->payment->amount ;


                    if(auth()->user()->hasRole('salon owner') ){
                        // 🔴 CAS 2: Le SALON annule
                        Log::info('🔴 SALON ANNULE', [
                            'purchase_amount' => $purchaseamount,
                            'service_total' => $booking->getTotal()
                        ]);

                        $salonW = $this->walletRepository->findWhere(['user_id' => auth()->user()->id,
                                                                        'name' => WalletType::PRINCIPAL->value,
                                                                    ])->first() ;
                        if($salonW == Null) throw new \Exception('a Salon dont have a wallet yet');

                        // Transaction 1: Salon rembourse au client ce qu'il a reçu
                        // IMPORTANT: Utiliser le montant RÉEL de la transaction wallet, pas un calcul
                        if($purchaseamount > 0) {
                            // Récupérer la transaction où le salon a été crédité
                            $salonTransaction = \App\Models\WalletTransaction::where('payment_id', $purchase->payment_id)
                                ->where('wallet_id', $salonW->id)
                                ->where('action', 'credit')
                                ->first();

                            // Utiliser le montant RÉEL de la transaction
                            $salonReceivedAmount = $salonTransaction ? $salonTransaction->amount : 0;

                            // ⚠️ IMPORTANT: Si coupon PLATEFORME, le client doit recevoir ce qu'il a PAYÉ
                            // Pas forcément ce que le salon a reçu
                            $couponDiscount = 0;
                            $clientPaidAmount = $purchaseamount; // Montant que le client a payé

                            if($purchase->coupon) {
                                $couponData = $this->paymentService->buildCouponData($purchase);
                                if($couponData['applies_to'] === 'platform') {
                                    $couponDiscount = $couponData['value'];
                                    // Le salon rembourse seulement ce que le client a payé
                                    $salonReceivedAmount = $clientPaidAmount;
                                }
                            }

                            Log::info('💰 Montant à rembourser par le salon', [
                                'montant_salon_a_recu' => $salonTransaction ? $salonTransaction->amount : 0,
                                'montant_client_a_paye' => $clientPaidAmount,
                                'coupon_platform' => $couponDiscount,
                                'montant_a_rembourser' => $salonReceivedAmount,
                                'transaction_id' => $salonTransaction ? $salonTransaction->id : 'NULL'
                            ]);

                            array_push($payment_intents, [
                                "amount" => $salonReceivedAmount,  // Ce que le salon a reçu (900F)
                                "payer_wallet" => $salonW,
                                "user" => $booking->user,
                                "walletType" => $walletType,
                                "description" => "Remboursement du service (salon annule)"
                            ]);

                            Log::info('💸 Transaction 1: Salon → Client', [
                                'amount' => $salonReceivedAmount,
                                'description' => 'Remboursement ce que le salon a reçu'
                            ]);

                            // Transaction 2: Plateforme rembourse la commission au client
                            // SAUF si coupon plateforme (car le client n'a pas payé le montant complet)
                            if($purchase && $purchase->taxes && $couponDiscount == 0) {
                                $commission = PaymentService::getCommission($booking->getTotal(), $purchase->taxes);

                                array_push($payment_intents, [
                                    "amount" => $commission,
                                    "payer_wallet" => setting('app_default_wallet_id'),
                                    "user" => $booking->user,
                                    "walletType" => $walletType,
                                    "description" => "Remboursement commission (salon annule)"
                                ]);

                                // ⭐ TRACKING REVENUS PLATEFORME - Remboursement commission (montant négatif)
                                PlatformRevenue::create([
                                    'type' => PlatformRevenueType::COMMISSION->value,
                                    'amount' => -$commission,  // NÉGATIF = remboursement/perte
                                    'booking_id' => $booking->id,
                                    'salon_id' => $booking->salon->id,
                                    'customer_id' => $booking->user_id,
                                    'description' => sprintf(
                                        "Remboursement commission (salon annule réservation #%d) - %.1f%% de %sF",
                                        $booking->id,
                                        PaymentService::getCommissionRate($purchase->taxes),
                                        $booking->getTotal()
                                    )
                                ]);

                                Log::info('💸 Transaction 2: Plateforme → Client', [
                                    'amount' => $commission,
                                    'description' => 'Remboursement de la commission'
                                ]);

                                Log::info('💰 REVENU PLATEFORME - Remboursement commission enregistré', [
                                    'type' => 'commission_refund',
                                    'amount' => -$commission,
                                    'booking_id' => $booking->id,
                                    'reason' => 'salon_cancellation'
                                ]);
                            } elseif($couponDiscount > 0) {
                                Log::info('💰 Coupon plateforme détecté - Pas de remboursement de commission', [
                                    'coupon_value' => $couponDiscount,
                                    'raison' => 'Le client récupère seulement ce qu\'il a payé'
                                ]);
                            }
                        }

                        // Transaction 3: Pénalité d'annulation - Salon → Plateforme
                        if($cancellationCharge > 0) {
                            array_push($payment_intents, [
                                "amount" => $cancellationCharge,
                                "payer_wallet" => $salonW,
                                "user" => null,  // null = plateforme
                                "description" => "Pénalité d'annulation par le salon"
                            ]);

                            // ⭐ TRACKING REVENUS PLATEFORME - Pénalité salon
                            PlatformRevenue::create([
                                'type' => PlatformRevenueType::CANCELLATION_PENALTY->value,
                                'amount' => $cancellationCharge,
                                'booking_id' => $booking->id,
                                'salon_id' => $booking->salon->id,
                                'customer_id' => $booking->user_id,
                                'description' => sprintf(
                                    "Pénalité d'annulation par le salon (réservation #%d)",
                                    $booking->id
                                )
                            ]);

                            Log::info('💸 Transaction 3: Salon → Plateforme (Pénalité)', [
                                'amount' => $cancellationCharge
                            ]);

                            Log::info('💰 REVENU PLATEFORME - Pénalité salon enregistrée', [
                                'type' => 'penalty',
                                'amount' => $cancellationCharge,
                                'booking_id' => $booking->id,
                                'cancelled_by' => 'salon'
                            ]);
                        }

                        // NE PAS rembourser les frais de réservation (booking_price) - déjà encaissés

                    }

                    // 🔵 CAS 1: Le CLIENT annule (si ce n'est PAS un salon owner)
                    if(!auth()->user()->hasRole('salon owner')){
                        Log::info('🔵 CLIENT ANNULE', [
                            'purchase_amount' => $purchaseamount,
                            'service_total' => $booking->getTotal()
                        ]);

                        // Le client doit recevoir le montant COMPLET (1000F)
                        // Salon rembourse 900F + Plateforme rembourse 100F
                        $salonUsers = $booking->salon?->users ?? collect();
                        Log::info(['les utilisateurs du salon ',$salonUsers->toArray()] );

                        if(!$salonUsers->isEmpty()) {
                            $salonW = $this->walletRepository->findWhere(['user_id' => $salonUsers->first()->id ,
                                                                        'name' => WalletType::PRINCIPAL->value,
                                                                    ])->first() ;

                            // Transaction 1: Salon rembourse au client ce qu'il a reçu
                            // IMPORTANT: Utiliser le montant RÉEL de la transaction wallet
                            if($purchaseamount > 0) {
                                // Récupérer la transaction où le salon a été crédité
                                $salonTransaction = \App\Models\WalletTransaction::where('payment_id', $purchase->payment_id)
                                    ->where('wallet_id', $salonW->id)
                                    ->where('action', 'credit')
                                    ->first();

                                // Utiliser le montant RÉEL de la transaction
                                $salonReceivedAmount = $salonTransaction ? $salonTransaction->amount : 0;

                                Log::info('💰 Montant réel reçu par le salon (depuis wallet transaction)', [
                                    'salon_received_amount' => $salonReceivedAmount,
                                    'transaction_id' => $salonTransaction ? $salonTransaction->id : 'NULL'
                                ]);

                                array_push($payment_intents, [
                                    "amount" => $salonReceivedAmount,  // 900F
                                    "payer_wallet" => $salonW,
                                    "user" => $booking->user,
                                    "walletType" => $walletType,
                                    "description" => "Remboursement du service (client annule)"
                                ]);

                                Log::info('💸 Transaction 1: Salon → Client', [
                                    'amount' => $salonReceivedAmount,
                                    'description' => 'Remboursement ce que le salon a reçu'
                                ]);

                                // Transaction 2: Plateforme rembourse la commission (100F)
                                if($purchase && $purchase->taxes) {
                                    $commission = PaymentService::getCommission($booking->getTotal(), $purchase->taxes);

                                    array_push($payment_intents, [
                                        "amount" => $commission,  // 100F
                                        "payer_wallet" => setting('app_default_wallet_id'),
                                        "user" => $booking->user,
                                        "walletType" => $walletType,
                                        "description" => "Remboursement commission (client annule)"
                                    ]);

                                    // ⭐ TRACKING REVENUS PLATEFORME - Remboursement commission (montant négatif)
                                    PlatformRevenue::create([
                                        'type' => PlatformRevenueType::COMMISSION->value,
                                        'amount' => -$commission,  // NÉGATIF = remboursement/perte
                                        'booking_id' => $booking->id,
                                        'salon_id' => $booking->salon->id,
                                        'customer_id' => $booking->user_id,
                                        'description' => sprintf(
                                            "Remboursement commission (client annule réservation #%d) - %.1f%% de %sF",
                                            $booking->id,
                                            PaymentService::getCommissionRate($purchase->taxes),
                                            $booking->getTotal()
                                        )
                                    ]);

                                    Log::info('💸 Transaction 2: Plateforme → Client', [
                                        'amount' => $commission,
                                        'description' => 'Remboursement de la commission'
                                    ]);

                                    Log::info('💰 REVENU PLATEFORME - Remboursement commission enregistré', [
                                        'type' => 'commission_refund',
                                        'amount' => -$commission,
                                        'booking_id' => $booking->id,
                                        'reason' => 'client_cancellation'
                                    ]);
                                }
                            }

                            // Transaction 3: Pénalité - Client → Plateforme
                            if($cancellationCharge > 0 && $clientW) {
                                array_push($payment_intents, [
                                    "amount" => $cancellationCharge,
                                    "payer_wallet" => $clientW,
                                    "user" => null,  // null = plateforme
                                    "walletType" => $walletType,
                                    "description" => "Pénalité d'annulation par le client"
                                ]);

                                // ⭐ TRACKING REVENUS PLATEFORME - Pénalité client
                                PlatformRevenue::create([
                                    'type' => PlatformRevenueType::CANCELLATION_PENALTY->value,
                                    'amount' => $cancellationCharge,
                                    'booking_id' => $booking->id,
                                    'salon_id' => $booking->salon->id,
                                    'customer_id' => $booking->user_id,
                                    'description' => sprintf(
                                        "Pénalité d'annulation par le client (réservation #%d)",
                                        $booking->id
                                    )
                                ]);

                                Log::info('💸 Transaction 3: Client → Plateforme (Pénalité)', [
                                    'amount' => $cancellationCharge
                                ]);

                                Log::info('💰 REVENU PLATEFORME - Pénalité client enregistrée', [
                                    'type' => 'penalty',
                                    'amount' => $cancellationCharge,
                                    'booking_id' => $booking->id,
                                    'cancelled_by' => 'client'
                                ]);
                            }

                        } else {
                            // Pas de salon trouvé - plateforme rembourse tout
                            if($purchaseamount > 0) {
                                array_push($payment_intents, [
                                    "amount" => $booking->getTotal(),
                                    "payer_wallet" => setting('app_default_wallet_id'),
                                    "user" => $booking->user,
                                    "walletType" => $walletType,
                                    "description" => "Remboursement complet par la plateforme"
                                ]);
                            }

                            // Pénalité même si pas de salon
                            if($cancellationCharge > 0 && $clientW) {
                                array_push($payment_intents, [
                                    "amount" => $cancellationCharge,
                                    "payer_wallet" => $clientW,
                                    "user" => null,
                                    "walletType" => $walletType,
                                    "description" => "Pénalité d'annulation par le client"
                                ]);

                                // ⭐ TRACKING REVENUS PLATEFORME - Pénalité client (sans salon)
                                PlatformRevenue::create([
                                    'type' => PlatformRevenueType::CANCELLATION_PENALTY->value,
                                    'amount' => $cancellationCharge,
                                    'booking_id' => $booking->id,
                                    'salon_id' => null,
                                    'customer_id' => $booking->user_id,
                                    'description' => sprintf(
                                        "Pénalité d'annulation par le client (réservation #%d - pas de salon trouvé)",
                                        $booking->id
                                    )
                                ]);

                                Log::info('💰 REVENU PLATEFORME - Pénalité client enregistrée (sans salon)', [
                                    'type' => 'penalty',
                                    'amount' => $cancellationCharge,
                                    'booking_id' => $booking->id
                                ]);
                            }
                        }

                        // NE PAS rembourser les frais de réservation (booking_price)
                    }
                }else{
                    // Pas de purchase - remboursement par la plateforme
                    if($booking->payment->amount > 0) {
                        array_push($payment_intents, [
                            "amount" => $booking->payment->amount,
                            "payer_wallet" => setting('app_default_wallet_id'),
                            "user" => $booking->user,
                            "walletType" => $walletType,
                            "description" => "Remboursement (pas de purchase)"
                        ]);
                    }
                }

                if($purchase) {
                    $purchase = $this->purchaseRepository->update([ 'purchase_status_id' => 3 ,
                        ], $purchase->id);
                }
            }

            else if($booking->booking_status_id == 9 && $booking->payment->paymentStatus_id != 3){
                //si le statut de la reservation est reporté et que le statut du paiement est tout sauf failed
                Log::Error(['about do do payement transactions about report']);
                
                if(auth()->user()->hasRole('salon owner') ){
                   // c'est le coiffeur qui reporte
                    $salonW = $this->walletRepository->findByField('user_id',  auth()->user()->id)->first() ;
                    if($salonW == Null) throw new \Exception('user dont have a wallet yet');
                    //le coiffeur verse une commision à l'appli
                    array_push($payment_intents ,  ["amount"=>  setting('postpone_charge', 0 ),"payer_wallet"=>$salonW, "user"=> null] );
                }

                if(auth()->user()->hasRole('customer') ){ 
                    // c'est le client qui reporte
                    [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;
    
                    if($clientW == Null) throw new \Exception('user dont have a wallet yet');
                    //le client verse une commision à l'appli
                    array_push($payment_intents ,  ["amount"=> setting('postpone_charge', 0 ) ,"payer_wallet"=>$clientW, "user"=> null , "walletType"=> $walletType] );
                }
            }
            
            else if($booking->booking_status_id == 4 && $booking->payment->paymentStatus_id != 3 && $booking->payment->paymentMethod->name == 'Wallet'){
                //si le statut de la reservation est accepted et que le statut du paiement de la reservation est tout sauf failed
                $is_pyment_cash = false ;
                
                //le montant du service (montant de l'achat)
                // $purchaseamount = $booking->getSubtotal(); 
                $purchaseamount = $booking->getTotal(); 
                // dd( [$booking->getSubtotal() , $booking->getCouponValue() , $booking->getTotal()]) ;

                //et si le booking n'est pas lié à un report
                if(auth()->user()->hasRole('salon owner') && is_null($booking->reported_from_id) ){
                    // si acceptation de la reservation est faite par le coiffeur
                   
                    // Chercher TOUS les purchases pour ce booking pour debug
                    $allPurchases = $this->purchaseRepository->scopeQuery(function ($query) use ($booking) {
                        return $query->whereRaw("JSON_EXTRACT(booking, '$.id') = ?", [$booking->id]);
                    })->get();

                    Log::info('UpdateBookingPaymentListener - Tous les purchases pour booking '.$booking->id.':', [
                        'count' => $allPurchases->count(),
                        'payment_method' => $booking->payment->paymentMethod->name ?? 'NULL',
                        'purchases' => $allPurchases->map(function($p) {
                            return [
                                'id' => $p->id,
                                'hint' => $p->hint,
                                'purchase_status_id' => $p->purchase_status_id
                            ];
                        })->toArray()
                    ]);

                    // Chercher d'abord un purchase avec hint='wallet' (créé lors du paiement initial)
                    $walletPurchase = $this->purchaseRepository->scopeQuery(function ($query) use ($booking) {
                        return $query->whereRaw("JSON_EXTRACT(booking, '$.id') = ?", [$booking->id])
                                    ->where("purchase_status_id", 1)
                                    ->where('hint', 'wallet');
                    })->first();

                    if($walletPurchase) {
                        // C'est un paiement wallet - utiliser le purchase existant
                        $is_pyment_cash = false;
                        $purchase = $walletPurchase;
                        Log::info('UpdateBookingPaymentListener - Purchase wallet trouvé:', [
                            'purchase_id' => $purchase->id,
                            'hint' => $purchase->hint
                        ]);
                    } else {
                        Log::info('UpdateBookingPaymentListener - Aucun purchase wallet trouvé, recherche purchase cash...');
                        // Chercher un purchase cash (hint='cash' ou sans hint)
                        $cashPurchase = $this->purchaseRepository->scopeQuery(function ($query) use ($booking) {
                            return $query->whereRaw("JSON_EXTRACT(booking, '$.id') = ?", [$booking->id])
                                        ->where("purchase_status_id", 1)
                                        ->where(function($q) {
                                            $q->where('hint', 'cash')
                                              ->orWhereNull('hint');
                                        });
                        })->first();

                        if($cashPurchase) {
                            $is_pyment_cash = true;
                            $purchase = $this->purchaseRepository->update(['taxes'=>  $booking->purchase_taxes], $cashPurchase->id);
                            Log::info('UpdateBookingPaymentListener - Purchase cash trouvé et mis à jour:', [
                                'purchase_id' => $purchase->id,
                                'hint' => $purchase->hint
                            ]);
                        } else {
                            //Aucun purchase existant - créer un nouveau (cas rare)
                            Log::warning('UpdateBookingPaymentListener - Aucun purchase trouvé, création d\'un nouveau');
                            $purchase = $this->purchaseRepository->Create([
                                'salon' => $booking->salon ,
                                'booking' => $booking,
                                'e_services' => $booking->e_services ,
                                'options' => $booking->options ,
                                'quantity' => $booking->quantity,
                                'user_id' => $booking->user_id ,
                                'taxes'=>  $booking->purchase_taxes ,
                                'coupon'=>  $booking->coupon ,
                                'purchase_status_id' => 1 ,
                                'purchase_at'  => now()
                            ]);
                        }
                    }


                    [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;

                    Log::info('UpdateBookingPaymentListener - Acceptation:', [
                        'booking_id' => $booking->id,
                        'purchaseamount' => $purchaseamount,
                        'clientW' => $clientW ? $clientW->id : 'NULL',
                        'walletType' => $walletType ? $walletType->value : 'NULL',
                        'is_pyment_cash' => $is_pyment_cash,
                        'taxes' => $purchase->taxes
                    ]);

                    //si il y a eu achat de service
                    if ($is_pyment_cash == false && !is_null($clientW) ) {

                        $currency = json_decode($clientW->currency, true);

                        if ($currency['code'] == setting('default_currency_code')) {
                            Log::info('Creating payment from client to salon', [
                                'amount' => $purchaseamount,
                                'from_wallet' => $clientW->id,
                                'from_wallet_type' => $walletType->value,
                                'to_user' => auth()->user()->id,
                                'to_wallet_type' => WalletType::PRINCIPAL->value
                            ]);

                            // IMPORTANT: Le salon reçoit TOUJOURS sur son wallet PRINCIPAL (Igris)
                            // Mais le client paie depuis son wallet choisi ($walletType)
                            $purchasepayment = $this->paymentService->createPayment($purchaseamount,$clientW ,auth()->user(),WalletType::PRINCIPAL,$purchase->taxes,$this->paymentService->buildCouponData($purchase));
                            $purchasepayment = $purchasepayment[0];

                            Log::info('Payment created:', [
                                'payment_id' => $purchasepayment ? $purchasepayment->id : 'NULL'
                            ]);
                            if($purchasepayment){

                                try{
                                    //mise à jour du purchase comme étant payé et validé
                                    $purchase = $this->purchaseRepository->update(['payment_id' => $purchasepayment->id , 'purchase_status_id' => 2  ], $purchase->id);

                                    // ⭐ TRACKING REVENUS PLATEFORME - Commission
                                    if($purchase->taxes > 0) {
                                        $commission = PaymentService::getCommission($booking->getTotal(), $purchase->taxes);

                                        PlatformRevenue::create([
                                            'type' => PlatformRevenueType::COMMISSION->value,
                                            'amount' => $commission,
                                            'booking_id' => $booking->id,
                                            'salon_id' => $booking->salon->id,
                                            'customer_id' => $booking->user_id,
                                            'description' => sprintf(
                                                "Commission %.1f%% sur réservation #%d (service: %sF)",
                                                PaymentService::getCommissionRate($purchase->taxes),
                                                $booking->id,
                                                $booking->getTotal()
                                            )
                                        ]);

                                        Log::info('💰 REVENU PLATEFORME - Commission enregistrée', [
                                            'type' => 'commission',
                                            'amount' => $commission,
                                            'booking_id' => $booking->id,
                                            'rate' => PaymentService::getCommissionRate($purchase->taxes) . '%'
                                        ]);
                                    }

                                    // ⭐ TRACKING REVENUS PLATEFORME - Frais de réservation
                                    $bookingPrice = setting('booking_price', 0);
                                    if($bookingPrice > 0) {
                                        PlatformRevenue::create([
                                            'type' => PlatformRevenueType::BOOKING_FEE->value,
                                            'amount' => $bookingPrice,
                                            'booking_id' => $booking->id,
                                            'salon_id' => $booking->salon->id,
                                            'customer_id' => $booking->user_id,
                                            'description' => sprintf(
                                                "Frais de réservation pour réservation #%d",
                                                $booking->id
                                            )
                                        ]);

                                        Log::info('💰 REVENU PLATEFORME - Frais de réservation enregistrés', [
                                            'type' => 'booking_fee',
                                            'amount' => $bookingPrice,
                                            'booking_id' => $booking->id
                                        ]);
                                    }

                                    // ✅ Charger les transactions pour les notifications
                                    $purchasepayment->load('transactions');

                                    // ✅ Déclencher les notifications de paiement (client + salon)
                                    event(new NotifyPaymentEvent($purchasepayment, $clientW, auth()->user()));

                                    Log::info('Notifications de paiement déclenchées', [
                                        'payment_id' => $purchasepayment->id,
                                        'transactions_count' => $purchasepayment->transactions->count()
                                    ]);

                                } catch (Exception $e) {
                                    Log::error($e->getMessage());
                                }
                            }
                        }
                    }else if( $is_pyment_cash == true ){
                            //ne pas retirer le cout du service mais juste la commission
                        $input = [];
                        $input['payment']['amount'] = $purchaseamount;
                        $input['payment']['description'] = "payement done to user : ". strval(auth()->user()->id) ." .  ". strval(auth()->user()->name) ;
                        $input['payment']['payment_status_id'] = 1; // pending
                        $input['payment']['payment_method_id'] = 14; // cash
                        $input['payment']['user_id'] =  $booking->user->id;
                        $salonW = $this->walletRepository->findByField('user_id' ,auth()->user()->id)->first() ;  
                        if($salonW == Null) throw new \Exception('salon user dont have a wallet yet');
            
                        
                        $this->paymentService->intentCashPayment( $input,$salonW,$purchase->taxes);
                        //$payment = $this->paymentService->update(['payment_status_id' => 2 ], $payment->id);
                        
                        
                    } else {
                        Log::Error(['DebitCustomerForService','no default_currency_code in setting']);
                    }
                }
            }

            if(!empty($payment_intents)){
                Log::info('payment:' , $payment_intents);
                foreach ($payment_intents as $value) {
                    event(new DoPaymentEvent($value));
                }
            }

        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
          
        }
    }
}
            

       
