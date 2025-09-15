<?php

namespace App\Listeners;

use App\Criteria\Purchases\PaidPurchasesCriteria;
use App\Criteria\Purchases\PurchasesByBookingCriteria;
use App\Criteria\Purchases\PurchasesOfUserCriteria;
use App\Events\DoPaymentEvent;
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
        // Vérifie si le paiement est avec le wallet et non encore validé
        if ($booking->payment->payment_status_id != 3 
            && $booking->payment->paymentMethod->name === 'Wallet') {
            
            $walletTransaction = $this->walletTransactionRepository->findWhere([
                'user_id'    => $booking->user_id,
                'payment_id' => $booking->payment_id,
            ])->first();

            if (!$walletTransaction) {
                return [ null , null];
            }

            // Déterminer le type de wallet utilisé
            $walletType = $walletTransaction->wallet->name ?? null;

            $walletType = ($walletType === WalletType::BONUS->value) 
                            ? WalletType::BONUS 
                            : WalletType::PRINCIPAL;

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
            $payment_intents =[];
            Log::info(['eefefefefefefe', $booking->getOriginal()]) ;
            if( in_array($booking->booking_status_id, [7, 8]) && $booking->getOriginal()['booking_status_id'] < 4){
                
                [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;

                if($booking->payment->amount > 0) array_push($payment_intents ,  ["amount"=>$booking->payment->amount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=> $booking->user , "walletType"=> $walletType ] );


            }else
            if( in_array($booking->booking_status_id, [7, 8]) && $booking->payment->payment_status_id != 3){
                //si le statut de la reservation est failed et que le statut du paiement est tout sauf failed
                //le montant de la reservation
                $purchaseamount = 0  ;
                $purchasepayment = Null ;

                [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;


                //si il y a eu achat le montant de l'achat'
                $this->purchaseRepository->pushCriteria(new PurchasesOfUserCriteria(auth()->id()));
                $this->purchaseRepository->pushCriteria(new PurchasesByBookingCriteria());
                $this->purchaseRepository->pushCriteria(new PaidPurchasesCriteria());
                $purchase = $this->purchaseRepository->get()->first(function ($purchase)  use ($booking) {
                                return $purchase->booking && $purchase->booking->id == $booking->id;
                        }) ;

                if($purchase) {
                    $purchaseamount = $purchase->payment->amount ;
                    $purchasepayment = $purchase->payment ;
                }

                if(auth()->user()->hasRole('salon owner') ){
                    // c'est le coiffeur qui annule
                    $salonW = $this->walletRepository->findWhere(['user_id' => auth()->user()->id,
                                                                    'name' => WalletType::PRINCIPAL->value,
                                                                ])->first() ;
                    if($salonW == Null) throw new \Exception('a Salon dont have a wallet yet');
                    //le coiffeur rembourse au client le montant du service
                    //si il y a eu achat de service
                    if($purchaseamount > 0 ) array_push($payment_intents ,  ["amount"=>$purchaseamount,"payer_wallet"=>$salonW, "user"=> $booking->user , "walletType"=> $walletType  , "taxes" => ($purchase)? $purchase->taxes : Null ] );
                    array_push($payment_intents ,  ["amount"=>  setting('postpone_charge', 0 ),"payer_wallet"=>$salonW, "user"=> null] );
                    if($booking->payment->amount > 0) array_push($payment_intents ,  ["amount"=>$booking->payment->amount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=> $booking->user , "walletType"=> $walletType ] );

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
                    array_push($payment_intents ,  ["amount"=> setting('postpone_charge', 0 ) ,"payer_wallet"=>$clientW, "user"=> null , "walletType"=> $walletType] );
                    
                }



                if($purchase) {
                    $purchase = $this->purchaseRepository->update([ 'purchase_status_id' => 3 ,
                        ], $purchase->id);
                }
            }

            else if($booking->booking_status_id == 9 && $booking->payment->payment_status_id != 3){
                //si le statut de la reservation est reporté et que le statut du paiement est tout sauf failed
                Log::Error(['about do do payement transactions about report']);
                
                if(auth()->user()->hasRole('salon owner') ){
                   // c'est le coiffeur qui reporte
                    $salonW = $this->walletRepository->findByField('user_id',  auth()->user()->id)->first() ;
                    if($salonW == Null) throw new \Exception('user dont have a wallet yet');
                    //le coiffeur verse une commision à l'appli
                    array_push($payment_intents ,  ["amount"=>  setting('postpone_charge', 0 ),"payer_wallet"=>$salonW, "user"=> null] );
                    // array_push($payment_intents ,  ["amount"=> setting('postpone_charge', 1000),"payer_wallet"=>$salonW, "user"=> null] );
                }

                if(auth()->user()->hasRole('customer') ){ 
                    // c'est le client qui reporte
                    [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;
    
                    if($clientW == Null) throw new \Exception('user dont have a wallet yet');
                    //le client verse une commision à l'appli
                    array_push($payment_intents ,  ["amount"=> setting('postpone_charge', 0 ) ,"payer_wallet"=>$clientW, "user"=> null , "walletType"=> $walletType] );
                }
            }
            
            else if($booking->booking_status_id == 4 && $booking->payment->payment_status_id != 3 && $booking->payment->paymentMethod->name == 'Wallet'){
                //si le statut de la reservation est accepted et que le statut du paiement de la reservation est tout sauf failed
                $is_pyment_cash = false ;
                
                //le montant du service (montant de l'achat)
                $purchaseamount = $booking->getSubtotal(); 

                //et si le booking n'est pas lié à un report
                if(auth()->user()->hasRole('salon owner') && is_null($booking->reported_from_id) ){
                    // si acceptation de la reservation est faite par le coiffeur
                    
                    [$clientW, $walletType] = $this->getWalletUseToPayBooking($booking) ;

                    if($clientW  == Null) throw new \Exception('client  dont have a wallet yet');
                    
                    //dans le cas de paiement par cash jai crée un purchase à pending
                    //je verifie sil y en a pour savoir si cest un paiement cash
                    $purchase = $this->purchaseRepository ->scopeQuery(function ($query) use ($booking) {
                            return $query->whereRaw("JSON_EXTRACT(booking, '$.id') = ?", [$booking->id])->where("purchase_status_id", 1);
                        })->first();
                    
                    if(!is_null($purchase) ){ 
                        $is_pyment_cash = true ;
                        $purchase = $this->purchaseRepository->update(['taxes'=>  $booking->purchase_taxes], $purchase->id);
                                       
                    }else{
                        //si ce n'est pas null c'est pas un paiement cash
                        
                        $purchase = $this->purchaseRepository->Create([
                            'salon' => $booking->salon ,
                            'booking' => $booking,
                            'e_services' => $booking->e_services ,
                            'quantity' => $booking->quantity,
                            'user_id' => $booking->user_id ,
                            'taxes'=>  $booking->purchase_taxes ,
                            'purchase_status_id' => 1 ,
                            'purchase_at'  => now()  
                        ]);
                    }

                   
                    $currency = json_decode($clientW->currency, true);
                    //si il y a eu achat de service
                    if($purchase){
                        if ($is_pyment_cash == false && $currency['code'] == setting('default_currency_code')) {
                            
                            $payment = $this->paymentService->createPayment($purchaseamount,$clientW ,auth()->user(),Null,$purchase->taxes);
                            $payment = $payment[0];
                            if($payment){
                                
                                try{ 
                                    if($booking->payment->paymentMethod->name == 'Wallet'){
                                        $purchase = $this->purchaseRepository->update(['payment_id' => $payment->id , 'purchase_status_id' => 2  ], $purchase->id);
                                    }
                                    
                                } catch (Exception $e) {
                                    Log::error($e->getMessage());
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
                
                           
                            $payment = $this->paymentService->intentCashPayment( $input,$salonW,$purchase->taxes);
                            //$payment = $this->paymentService->update(['payment_status_id' => 2 ], $payment->id);
                            
                           
                        } else {
                            Log::Error(['DebitCustomerForService','no default_currency_code in setting']);
                        }
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
            

       
