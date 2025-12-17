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


                //si il y a eu achat trouver le montant de l'achat'
                $this->purchaseRepository->pushCriteria(new PurchasesOfUserCriteria(auth()->id()));
                $this->purchaseRepository->pushCriteria(new PurchasesByBookingCriteria());
                $this->purchaseRepository->pushCriteria(new PaidPurchasesCriteria());
                $purchase = $this->purchaseRepository->get()->first(function ($purchase)  use ($booking) {
                                return $purchase->booking && $purchase->booking->id == $booking->id;
                        }) ;

                if($purchase) {
                    
                    if($purchase->purchaseStatus->order == 50) $purchaseamount = $purchase->payment->amount ;
                    

                    if(auth()->user()->hasRole('salon owner') ){
                        // c'est le coiffeur qui annule
                        $salonW = $this->walletRepository->findWhere(['user_id' => auth()->user()->id,
                                                                        'name' => WalletType::PRINCIPAL->value,
                                                                    ])->first() ;
                        if($salonW == Null) throw new \Exception('a Salon dont have a wallet yet');
                        //le coiffeur rembourse au client le montant du service
                        //si il y a eu achat de service
                        if($purchaseamount > 0 ) array_push($payment_intents ,  ["amount"=>$purchaseamount,"payer_wallet"=>$salonW, "user"=> $booking->user , "walletType"=> $walletType  , "taxes" => ($purchase)? $purchase->taxes : Null ] );
                        if($booking->payment->amount > 0) array_push($payment_intents ,  ["amount"=>$booking->payment->amount,"payer_wallet"=>$salonW, "user"=> $booking->user , "walletType"=> $walletType ] );

                    }
                    
                    if(auth()->user()->hasRole('customer') ){ 
                    
                        // c'est le client qui annule  
                        $salonUsers = $booking->salon?->users ?? collect();
                        Log::info(['les utilisateurs du salon ',$salonUsers->toArray()] );
        
                        if(!$salonUsers->isEmpty()){ ;
                            $salonW = $this->walletRepository->findWhere(['user_id' => $salonUsers->first()->id ,
                                                                        'name' => WalletType::PRINCIPAL->value,
                                                                    ])->first() ;        
                            //le coiffeur rembourse au client le montant du service
                            //si il y a eu achat de service
                            if($purchaseamount > 0) array_push($payment_intents ,  ["amount"=>$purchaseamount,"payer_wallet"=>$salonW, "user"=> $booking->user  , "walletType"=> $walletType , "taxes" => ($purchase)? $purchase->taxes : Null ] );
                        }else{
                            if($purchaseamount > 0) array_push($payment_intents ,  ["amount"=>$purchaseamount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=> $booking->user , "walletType"=> $walletType , "taxes" => ($purchase)? $purchase->taxes : Null ] );
                        }
                        
                    }
                }else{
                    
                    if($booking->payment->amount > 0) array_push($payment_intents ,  ["amount"=>$booking->payment->amount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=> $booking->user , "walletType"=> $walletType ] );

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

                                    // ✅ Déclencher les notifications de paiement (client + salon)
                                    event(new NotifyPaymentEvent($purchasepayment, $clientW, auth()->user()));

                                    // ✅ Déclencher la notification de changement de status du booking (accepté)
                                    event(new NotifyBookingEvent($booking));

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
            

       
