<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Events\NotifyPaymentEvent;
use App\Models\Booking;
use App\Notifications\RechargePayment;
use App\Notifications\WithdrawPayment;
use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletTransactionRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Tax;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewReceivedPayment;
use App\Repositories\PaymentMethodRepository;
use App\Types\PaymentType;
use App\Types\WalletType;


class PaymentService
{
    private $bookingRepository;
    public $walletRepository;
    private $currencyRepository;
    private $walletTransactionRepository;
    private $paymentRepository;
    
    private $currency ;

    public function __construct(
        BookingRepository $bookingRepository,
        WalletRepository $walletRepository,
        CurrencyRepository $currencyRepository,
        WalletTransactionRepository $walletTransactionRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->walletRepository = $walletRepository;
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->currencyRepository = $currencyRepository ;
        $this->currency = $this->currencyRepository->findWithoutFail(setting('default_currency_id'));
    }

    public function buildCouponData(Booking|Purchase $booking){
        $coupon = $booking->coupon ;
        $applies_to = 'platform' ;
        if ($coupon->discountable_type  == "App\Models\Wallet") $applies_to = 'platform' ;
        if (in_array( $coupon->discountable_type , ["App\Models\EService" , "App\Models\Salon" ,"App\Models\Category" ]))  $applies_to = 'salon' ;
          
        return ['applies_to' =>  $applies_to ,'value' => $booking->getCouponValue() ] ;
    }
 

     /**
    * make Payment .
    * This method processes a payment transaction where a payer initiates the payment
    * to a specified receiver. It requires the receiver's user object and the payer's
    * wallet, which can be either an integer identifier or a Wallet object.
    *
    * @param float $amount The amount of the payment.
    * @param Int|String|Wallet $payer_wallet The wallet identifier or wallet of the payer initiating the payment.
    * @param User  $user The user receiving the payment.
    * @param WalletType|Null  $wallettype
    * @param Tax|Tax[]|null $tax paramètre pour la commission
    * @return Array|Null
    */
    public function createPayment(float $amount ,Int|String|Wallet $payer_wallet ,User $receiver = new User() , WalletType|Null $wallettype = Null , $tax = Null ,?array $coupon = null  ) : array | Null
    {
        
        $payer_wallet = $this->resolveWallet($payer_wallet);
        $wallettype =  !is_null($wallettype)? $wallettype->value : WalletType::PRINCIPAL->value ;
        $taxLog = is_array($tax)
            ? array_map(function ($t) {
                return is_object($t) && isset($t->id) ? $t->id : $t;
            }, $tax)
            : (is_object($tax) && isset($tax->id) ? $tax->id : $tax);
        Log::info('PaymentService::createPayment start', [
            'amount' => $amount,
            'payer_wallet_id' => $payer_wallet?->id,
            'payer_user_id' => $payer_wallet?->user_id,
            'receiver_id' => $receiver?->id,
            'wallet_type' => $wallettype,
            'tax' => $taxLog,
            'coupon' => $coupon,
        ]);
        // if($receiver->id != null){ 
        //     $wallet = ($wallettype !== null) ? $this->walletRepository->findWhere([
        //                                                             'user_id' => $receiver->id,
        //                                                             'name'    => $wallettype->value,
        //                                                         ])->first() 
        //                         : $this->walletRepository->findWhere(['user_id' =>$receiver->id,
        //                                                             'name' => WalletType::PRINCIPAL->value,
        //                                                         ])->first() ;
        // }else{
        //     $wallet =  $this->walletRepository->find(setting('app_default_wallet_id'));
        // }

        // if($wallet == Null){
        //     $wallet = ($wallettype == null )? $this->createWallet($receiver, 0) : $this->createWallet($receiver, 0, $wallettype->value);
        // }

        $receiverWallet = $this->resolveReceiverWallet($receiver, $wallettype);

        $currency = json_decode($receiverWallet->currency, true);
        $payment = Null ;
        if ($currency['code'] == setting('default_currency_code')) {
           
            if($amount >= 0) { 
                try{
                    $payment = $this->toWalletFromWallet($this->buildInternalPaymentData($amount,$payer_wallet,$receiverWallet->user), [$receiverWallet , $payer_wallet] , $tax , $coupon) ;
                    if($amount > 0) event(new NotifyPaymentEvent($payment ,$payer_wallet ,$receiverWallet->user ));

                    return [$payment , $receiverWallet] ;
                } catch (Exception $e) {
                    Log::error('PaymentService::createPayment error', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
           
        }
        return [$payment , $receiverWallet] ; 
    }

     /**
    * make Payment .
    * This method processes a payment transaction where a payer initiates the payment
    * to a specified receiver. It requires the receiver's user object and the payer's
    * wallet, which can be either an integer identifier or a Wallet object.
    *
    * @param float $amount The amount of the payment.
    * @param Int|String|Wallet $payer_wallet The wallet identifier or wallet of the payer initiating the payment.
    * @param User  $user The user receiving the payment.
    * @param WalletType  $wallettype Paramètre optionnel pour le type de portefeuille
    * @return Array|Null
    */
    public function createPaymentToWallet(float $amount ,Int|String|Wallet $payer_wallet ,User $receiver = new User() ,  WalletType|Null  $wallettype = null ) : array | Null
    {
        
        $payer_wallet = $this->resolveWallet($payer_wallet);
        $wallettype =  !is_null($wallettype)? $wallettype->value : WalletType::PRINCIPAL->value ;


        // if($receiver->id != null){ 
        //     $receiverWallet = ($wallettype !== null) ? $this->walletRepository->findByField('user_id',  $receiver->id)
        //                                                             ->findByField('name',  $wallettype)->first() 
        //                         : $this->walletRepository->findByField('user_id',  $receiver->id)
        //                         ->findByField('name',  $wallettype)->first() ;
        // }else{
        //     $receiverWallet =  $this->walletRepository->find(setting('app_default_wallet_id'));
        // }

        // if($receiverWallet == Null){
        //     $receiverWallet = $this->createWallet($receiver , 0 , $wallettype) ;
        // }
        $receiverWallet = $this->resolveReceiverWallet($receiver, $wallettype);

        $currency = json_decode($receiverWallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
         
            if($amount > 0) { 
                try{
                    $payment = $this->toWalletFromWallet($this->buildInternalPaymentData($amount,$payer_wallet,$receiverWallet->user), [$receiverWallet , $payer_wallet]) ;
                    event(new NotifyPaymentEvent( $payment , $payer_wallet,$receiver  ));
                
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }
           
            return [$payment , $receiverWallet] ;
        }
        return Null ;
    }

    /**
     * Effectue une transaction de paiement externe.
     *
     * Cette méthode traite une transaction dans laquelle un Utilisateur est impliqué dans le paiement..
     * Le payeur peut être la plateforme (en cas de retrait) ou l'utilisateur lui-même (en cas de crédit).
     *
     * @param float       $amount Montant du paiement.
     * @param User|Wallet       $data   Utilisateur ou wallet impliqué dans le paiement.
     * @param PaymentType $type   Type de paiement : 'credit' (l'utilisateur est le payeur) ou 'debit' (la plateforme est le payeur).
     * @param WalletType $wallettype
     * @return array|null Détails de la transaction ou null en cas d’échec.
     */
    public function createPaymentLinkWithExternal(float $amount, User|Wallet $data, PaymentType $type, WalletType|Null $wallettype = null): ?array
    {
        try {
            
            $wallettype =  !is_null($wallettype)? $wallettype->value : WalletType::PRINCIPAL->value ;

            $user = null;
            $wallet = null;

            // Déterminer le type de données reçues
            if ($data instanceof Wallet) {
                $wallet = $data;
                $user = $wallet->user;
            }

            if ($data instanceof User) {
                $user = $data;
                $wallet = $this->walletRepository->findByField('user_id', $user->id)->first();
            }

            if (!$wallet) {
                if (!$user) {
                    throw new Exception('No user found to create wallet');
                }

                $wallet = ($wallettype == null) ? $this->createWallet($user, 0) : $this->createWallet($user, 0, $wallettype);
                if (!$wallet) {
                    throw new Exception('Failed to create wallet');
                }
            }

            
            // S'assurer qu'on a un utilisateur
            if (!$user && $wallet) {
                $user = $wallet->user;
            }

            $currency = json_decode($wallet->currency, true);
            if ($currency['code'] !== setting('default_currency_code')) {
                return null;
            }

            if ($amount == 0) {
                return [null, $wallet];
            }



            $payment = $this->withExternalTransaction(
                $this->buildExternalPaymentData($amount, $user, $type),
                $wallet,
                $type
            );



         
            if ($payment && $wallet->user) {
                try {
                    if($type == PaymentType::CREDIT){
                        NotificationService::notify([$wallet->user], new RechargePayment($payment, $wallet));
                    }else{
                        NotificationService::notify([$wallet->user], new WithdrawPayment($payment, $wallet));
                    }
                
                } catch (Exception $e) {
                    Log::error("Erreur lors de l'envoie de notification: " . $e->getMessage());
                }
            }
           
            return [$payment, $wallet];

        } catch (Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
           
            return null;
        }
    }


  
    /**
     * make Payment .
     * @param Array $input
     * @param Array $wallets
     * @param Tax|array|null $tax paramètre pour la commission
     * 
     * @return Payment | Null
     */
    private function toWalletFromWallet(Array $input , array $wallets, Tax|array|null $tax = null ,?array $coupon = null ){
        
        $receiverWallet =  $wallets[0] ;
        $payer_wallet =  $wallets[1] ;
        $currency = json_decode($receiverWallet->currency, true);
        $amount = $input['payment']['amount'];
        // Wallet plateforme
        $platformWallet = $this->walletRepository->find(setting('app_default_wallet_id'));
        $taxLog = is_array($tax)
            ? array_map(function ($t) {
                return is_object($t) && isset($t->id) ? $t->id : $t;
            }, $tax)
            : (is_object($tax) && isset($tax->id) ? $tax->id : $tax);

        Log::info('PaymentService::toWalletFromWallet start', [
            'payment_amount' => $amount,
            'receiver_wallet_id' => $receiverWallet->id,
            'receiver_user_id' => $receiverWallet->user_id,
            'payer_wallet_id' => $payer_wallet->id,
            'payer_user_id' => $payer_wallet->user_id,
            'currency_code' => $currency['code'] ?? null,
            'platform_wallet_id' => $platformWallet?->id,
            'coupon' => $coupon,
            'tax' => $taxLog,
        ]);

        if ($currency['code'] == setting('default_currency_code')) {

            $payment = $this->paymentRepository->create($input['payment']);

            Log::info('PaymentService::toWalletFromWallet payment created', [
                'payment_id' => $payment->id,
                'payment_input' => $input['payment'],
            ]);
           
           
            $discount = 0;
            $couponForSalon =  'platform' ;
            if ($coupon && $coupon['value'] > 0) {
                // Coupon applicable au salon ?
                $couponForSalon = $coupon['applies_to'] ?? 'platform'; // 'salon' ou 'platform'
                $discount       = $coupon['value'] ?? 0 ;
            }


             // Calcul de la commission si elle existe
            $commission = 0 ;
            if ($tax !== null && $amount > 0 ) {
                // Convertir les objets Tax en array si nécessaire
                $taxArray = is_array($tax)
                    ? array_map(function($t) {
                        return is_object($t) ? $t->toArray() : $t;
                      }, $tax)
                    : (is_object($tax) ? $tax->toArray() : $tax);

                $commission = self::getCommission($amount + $discount , $taxArray) ;
            }  

            Log::info('PaymentService::toWalletFromWallet commission', [
                'commission' => $commission,
                'tax' => $taxLog,
                'discount' => $discount,
                'couponForSalon' => $couponForSalon,
            ]);


            for ($i=0; $i <= 3  ; $i++) { 
                
                $transaction = [];
                $transaction['payment_id'] = $payment->id;
                if($i == 0){

                    $transaction['user_id'] = $receiverWallet->user_id;
                    $transaction['status'] = "completed" ;
                    $transaction['wallet_id'] = $receiverWallet->id;
                    $transaction['description'] = 'compte credité';
                    $transaction['action'] =  'credit';
                    $transaction['amount'] = $amount ;

                    // Logique commission : Si le RECEVEUR est un salon owner ET que le PAYEUR N'EST PAS un salon owner
                    // Cela couvre tous les cas : client avec rôle, client sans rôle, etc.
                    $payerIsSalonOwner = $payer_wallet->user->hasRole('salon owner');
                    $receiverIsSalonOwner = $receiverWallet->user->hasRole('salon owner');
                    $shouldApplyCommission = $receiverIsSalonOwner && !$payerIsSalonOwner;

                    Log::info('💰 DEBUG ROLES', [
                        'payer_user_id' => $payer_wallet->user->id,
                        'payer_roles' => $payer_wallet->user->roles->pluck('name')->toArray(),
                        'payer_is_salon_owner' => $payerIsSalonOwner,
                        'receiver_user_id' => $receiverWallet->user->id,
                        'receiver_roles' => $receiverWallet->user->roles->pluck('name')->toArray(),
                        'receiver_is_salon_owner' => $receiverIsSalonOwner,
                        'should_apply_commission' => $shouldApplyCommission
                    ]);

                    Log::info('💰 Transaction SALON (i=0) - AVANT déductions', [
                        'amount_initial' => $amount,
                        'discount' => $discount,
                        'commission' => $commission,
                        'is_customer_to_salon' => $shouldApplyCommission
                    ]);

                    if($shouldApplyCommission){

                        // LOGIQUE DES COUPONS ET COMMISSIONS:
                        // $amount = montant que le client a payé (déjà après réduction coupon)
                        // $discount = valeur du coupon
                        // $commission = calculée sur ($amount + $discount) donc sur le prix AVANT coupon

                        if ($discount > 0) {

                            if ($couponForSalon === 'salon') {
                                // COUPON SALON: Le salon offre la réduction
                                // - Client paie: $amount (prix réduit)
                                // - Salon reçoit: $amount - commission
                                // - Commission calculée sur prix RÉDUIT
                                // RIEN à faire ici, la logique normale s'applique
                                Log::info('💰 Coupon SALON', [
                                    'discount' => $discount,
                                    'amount_client_paie' => $amount,
                                    'salon_recevra' => $amount - $commission,
                                    'explication' => 'Salon offre réduction, commission sur prix réduit'
                                ]);
                            } else {
                                // COUPON PLATFORM: La plateforme offre la réduction
                                // - Client paie: $amount (prix réduit)
                                // - Salon DOIT recevoir: (prix_original - commission_sur_original)
                                // - Donc: ($amount + $discount) - $commission
                                // - On AJOUTE le discount pour que le salon reçoive le montant complet
                                $transaction['amount'] += $discount;
                                Log::info('💰 Coupon PLATFORM', [
                                    'discount' => $discount,
                                    'amount_client_paie' => $amount,
                                    'amount_avant_ajout_discount' => $amount,
                                    'amount_apres_ajout_discount' => $transaction['amount'],
                                    'salon_recevra' => $transaction['amount'] - $commission,
                                    'explication' => 'Platform offre réduction, salon reçoit prix complet moins commission'
                                ]);
                            }
                        }

                        //il a t'il une commission a prendre chez le coiffeur parce qu'il recoit
                        //de l'argent provenant du client
                        if(  $commission > 0 ) {
                            $transaction['amount'] -= $commission;
                            Log::info('💰 Commission déduite du SALON', [
                                'commission' => $commission,
                                'amount_final' => $transaction['amount']
                            ]);
                        }
                    }

                    Log::info('💰 Transaction SALON (i=0) - APRÈS déductions', [
                        'amount_final' => $transaction['amount']
                    ]);

                }
                
                if($i == 1){
                    
                    $transaction['user_id'] = $payer_wallet->user_id;
                    $transaction['status'] = "completed" ;
                    $transaction['wallet_id'] = $payer_wallet->id;
                    $transaction['description'] = 'compte débité';
                    $transaction['action'] =  'debit';
                    $transaction['amount'] = $amount ;

                 
                    if(  $commission > 0 &&  $payer_wallet->user->hasRole('salon owner') && $receiverWallet->user->hasRole('customer') ){
                        //il a t'il une commission a prendre chez le coiffeur parce qu'il recoit
                        //de l'argent provenant du client 
                        $transaction['amount'] -=  $commission;
                       
                    }
                       
                }
                if($i == 2){
                    
                    if(  $commission > 0 ){ 

                        $transaction['amount'] = $commission ;
                        $transaction['user_id'] = $platformWallet->user_id;
                        $transaction['status'] = "completed" ;
                        $transaction['wallet_id'] = $platformWallet->id;


                        if(  $payer_wallet->user->hasRole('customer') && $receiverWallet->user->hasRole('salon owner') ){
                            //il y a t'il une commission prise chez le coiffeur parce qu'il recoit
                            //de l'argent provenant du client 
                            $transaction['description'] = 'compte crédité';
                            $transaction['action'] =  'credit';

                        }else if( $payer_wallet->user->hasRole('salon owner') && $receiverWallet->user->hasRole('customer') ){
                            //y a t'il une commission à rembourser au client  parce qu'il avait payé
                            //de l'argent au coiffeur 
                            $transaction['description'] = 'compte débité';
                            $transaction['action'] =  'debit';
                        }

                    }
                    
                }

                if( $i == 3){
                    
                    
                    if ($discount > 0) {
                        
                        if(  $payer_wallet->user->hasRole('customer') && $receiverWallet->user->hasRole('salon owner') ){
                            // Coupon non pour le salon → la réduction vient de la plateforme
                            $transaction['amount']  = $discount ;
                            $transaction['user_id'] = $platformWallet->user_id;
                            $transaction['status'] = "completed" ;
                            $transaction['wallet_id'] = $platformWallet->id;
                            //y a t'il une commission à rembourser au client  parce qu'il avait payé
                            //de l'argent au coiffeur 
                            $transaction['description'] = 'compte débité';
                            $transaction['action'] =  'debit';
                        }

                    }
                }

                try{
                    if(count($transaction) > 1) {
                        Log::info('PaymentService::toWalletFromWallet create transaction', [
                            'step' => $i,
                            'transaction' => $transaction,
                        ]);
                        $o = $this->walletTransactionRepository->create($transaction);
                    }

                } catch (\Exception $e) {
                    Log::error('FAIL:'. $e->getMessage() , [
                        'trace' => $e->getTraceAsString()
                    ]);
                }

            }
            return $payment ;
        }
        Log::warning('PaymentService::toWalletFromWallet currency mismatch', [
            'currency_code' => $currency['code'] ?? null,
            'default_currency_code' => setting('default_currency_code'),
        ]);
        return Null ;
    }


    /**
     * make Payment .
     * @param Array $input
     * @param Wallet $wallet The wallet identifier or wallet of salon .
     * @param Tax|Tax[]|null $tax paramètre pour la commission
     * @param ?array $coupon = null
     * @return Payment | Null
     */
    public function intentCashPayment(Array $input , $wallet, $tax = null , ?array $coupon = null):Payment | Null
    {
        //si l'intension de payement est pour le coiffeur
        $amount = $input['payment']['amount'] ;

        $currency = json_decode($wallet->currency, true);
        if ($wallet->user->hasRole('salon owner')  && $currency['code'] == setting('default_currency_code')) {
                    

            if($amount > 0){
                $payment = $this->paymentRepository->create($input['payment']);

                $taxLog = is_array($tax)
                    ? array_map(function ($t) {
                        return is_object($t) && isset($t->id) ? $t->id : $t;
                    }, $tax)
                    : (is_object($tax) && isset($tax->id) ? $tax->id : $tax);

                Log::info('PaymentService::intentCashPayment start', [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'salon_wallet_id' => $wallet->id,
                    'salon_user_id' => $wallet->user_id,
                    'currency_code' => $currency['code'] ?? null,
                    'tax' => $taxLog,
                    'coupon' => $coupon,
                ]);

                $discount = 0;
                $couponForSalon =  'platform' ;
                if ($coupon && $coupon['value'] > 0) {
                    // Coupon applicable au salon ?
                    $couponForSalon = $coupon['applies_to'] ?? 'platform'; // 'salon' ou 'platform'
                    $discount       = $coupon['value'] ?? 0 ;
                }

                // Calcul de la commission si elle existe
                $commission = 0 ;
                if (!is_null($tax)) {
                    // Convertir les objets Tax en array si nécessaire
                    $taxArray = is_array($tax)
                        ? array_map(function($t) {
                            return is_object($t) ? $t->toArray() : $t;
                          }, $tax)
                        : (is_object($tax) ? $tax->toArray() : $tax);

                    Log::info('PaymentService::intentCashPayment tax before getCommission', [
                        'tax_original' => $tax,
                        'tax_converted' => $taxArray,
                        'amount' => $amount,
                        'discount' => $discount,
                    ]);

                    $commission = self::getCommission($amount + $discount, $taxArray) ;

                }

                Log::info('PaymentService::intentCashPayment commission/discount', [
                    'commission' => $commission,
                    'discount' => $discount,
                    'couponForSalon' => $couponForSalon,
                ]);
                
                for ($i=0; $i <= 3  ; $i++) { 
                    $transaction = [];
                    $transaction['payment_id'] = $payment->id;

                    if($i == 0){
                        //il a t'il une commission a prendre chez le coiffeur
                        if(  $commission > 0 ){

                            $transaction['user_id'] = $wallet->user_id;
                            $transaction['status'] = "completed" ;
                            $transaction['wallet_id'] = $wallet->id;
                            $transaction['description'] = 'compte débité';
                            $transaction['action'] =  'debit';
                            $transaction['amount'] = $commission;
                        }else{
                            continue ;
                        }
                    }
                    if($i == 1){
                        
                        if(  $commission > 0  ){
                            //il a t'il une commission prix chez le coiffeur parce qu'il recoit
                            //de l'argent provenant du client 
                            $transaction['amount'] = $commission ;
                            $w= $this->walletRepository->find(setting('app_default_wallet_id'));
                            $transaction['user_id'] = $w->user_id;
                            $transaction['status'] = "completed" ;
                            $transaction['wallet_id'] = $w->id;
                            $transaction['description'] = 'compte crédité';
                            $transaction['action'] =  'credit';
                        }else{
                            continue ;
                        }
                        
                    }
                    if( $discount > 0  ){
                        if($i == 2){
                            
                            if ($couponForSalon === 'salon') {
                                // Le salon prend en charge la réduction → on réduit le crédit du salon
                                //aucune action n'est faite
                            } else {
                                // Coupon non pour le salon → la réduction vient de la plateforme
                                $transaction['amount']  = $discount;
                                $w= $this->walletRepository->find(setting('app_default_wallet_id'));
                                $transaction['user_id'] = $w->user_id;
                                $transaction['status'] = "completed" ;
                                $transaction['wallet_id'] = $w->id;
                                $transaction['description'] = 'compte débité';
                                $transaction['action'] =  'debit';

                            }
                         
                        }

                        if($i == 3){
                            
                            if ($couponForSalon === 'salon') {
                                // Le salon prend en charge la réduction → on réduit le crédit du salon
                            //aucune action n'est faite
                            } else {
                                // Coupon non pour le salon → la réduction vient de la plateforme
                                $transaction['user_id'] = $wallet->user_id;
                                $transaction['status'] = "completed" ;
                                $transaction['wallet_id'] = $wallet->id;
                                $transaction['description'] = 'compte crédité';
                                $transaction['action'] =  'credit';
                                $transaction['amount']  = $discount;
                            }
                        }
                    }
                    
                    if(count($transaction) > 1) {
                        Log::info('PaymentService::intentCashPayment create transaction', [
                            'step' => $i,
                            'transaction' => $transaction,
                        ]);
                        $this->walletTransactionRepository->create($transaction);
                    } else {
                        Log::info('PaymentService::intentCashPayment skip empty transaction', [
                            'step' => $i,
                            'transaction' => $transaction,
                        ]);
                    }
                }
                return $payment ;
            }
        }
        Log::warning('PaymentService::intentCashPayment currency mismatch or amount invalid', [
            'amount' => $amount,
            'currency_code' => $currency['code'] ?? null,
            'default_currency_code' => setting('default_currency_code'),
        ]);
        return Null ;
    }


    private function resolveWallet(int|string|Wallet $wallet): Wallet
    {
        if ($wallet instanceof Wallet) {
            // Si c'est déjà un Wallet, charger les relations si pas déjà chargées
            if (!$wallet->relationLoaded('user')) {
                $wallet->load('user.roles');
            } elseif ($wallet->user && !$wallet->user->relationLoaded('roles')) {
                $wallet->user->load('roles');
            }
            return $wallet;
        }

        // Sinon, récupérer avec les relations user.roles chargées
        return $this->walletRepository->with('user.roles')->find($wallet);
    }


    private function resolveReceiverWallet(User $user, string|Null $walletType): Wallet
    {
        if (!$user->id) {
            // Wallet plateforme - charger avec user.roles
            return $this->walletRepository->with('user.roles')->find(setting('app_default_wallet_id'));
        }

        // Charger les rôles du user si pas déjà fait
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Récupérer le wallet avec la relation user.roles
        $wallet = $this->walletRepository->with('user.roles')->findWhere([
            'user_id' => $user->id,
            'name'    => !is_null($walletType)? $walletType  : WalletType::PRINCIPAL->value,
        ])->first();

        if (!$wallet) {
            $wallet = $this->createWallet($user, 0, !is_null($walletType)? $walletType  : WalletType::PRINCIPAL->value);
            // S'assurer que les relations sont chargées sur le wallet nouvellement créé
            $wallet->load('user.roles');
        }

        return $wallet;
    }

    /**
     * Traite une transaction de paiement externe avec enregistrement des mouvements sur les portefeuilles.
     *
     * Cette méthode crée un paiement, puis génère deux ou trois transactions associées :
     * - une transaction principale sur le portefeuille de l'utilisateur concerné (crédit ou débit),
     * - une transaction de frais éventuels sur le portefeuille de la plateforme.
     *
     * @param array   $input  Données du paiement (clé 'payment' attendue avec les champs requis).
     * @param Wallet  $wallet Portefeuille de l'utilisateur effectuant ou recevant le paiement.
     * @param PaymentType  $type   Type de transaction : 'credit' ou 'debit'.
     *
     * @return Payment|null   Objet Payment créé, ou null si la transaction est ignorée (montant nul ou devise invalide).
     */
    private function withExternalTransaction(Array $input , Wallet $wallet , PaymentType $type ):Payment | Null
    {
        $ptf_wallet =  $this->walletRepository->find(setting('app_default_wallet_id')) ; 
        if (!isset($ptf_wallet)) {
            throw new \Exception("Le portefeuille plateforme est introuvable.");
        }

        if (!isset($wallet)) {
            throw new \Exception("Le wallet principal est introuvable.");
        }
      
        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
            if($input['payment']['amount'] != 0){
                
                if (empty($input['payment']['payment_method_id']) || !app(PaymentMethodRepository::class)->find($input['payment']['payment_method_id'])) {
                    throw new \Exception("payment_method_id invalide ou manquant.");
                }
                
                $payment = $this->paymentRepository->create($input['payment']);
    
                $transaction = [];
                for ($i=0; $i <= 1  ; $i++) { 
                    
                    if($i == 0){
                        $transaction['payment_id'] = $payment->id;
                        $transaction['user_id'] = $wallet->user_id;
                        $transaction['status'] = "completed" ;
                        $transaction['amount'] = $input['payment']['amount'] - (($type == PaymentType::CREDIT)? setting('debit_fees',0)  : setting('debit_fees',0)) ;
                        $transaction['wallet_id'] = $wallet->id;
                        $transaction['description'] = ($type == PaymentType::CREDIT)? 'compte credité' : 'compte débité';
                        $transaction['action'] =  ($type == PaymentType::CREDIT)? 'credit' : 'debit';
                    }
                    if($i == 1  && ( ($type == PaymentType::CREDIT && setting('debit_fees',0) > 0) || ($type == PaymentType::DEBIT && setting('debit_fees',0) > 0) )){
                        $transaction['payment_id'] = $payment->id;
                        $transaction['user_id'] = $ptf_wallet->user_id;
                        $transaction['status'] = "completed" ;
                        $transaction['amount'] = ($type == PaymentType::CREDIT)? setting('debit_fees',0) : setting('debit_fees',0) ;
                        $transaction['wallet_id'] = $ptf_wallet->id;
                        $transaction['description'] = 'compte credité with transaction fee for external paiement #'.$payment->id;
                        $transaction['action'] =  'credit';
                    }
                    if(!empty($transaction)){
                        $this->walletTransactionRepository->create($transaction);
                    }
                    $transaction = [] ;
                }
                return $payment ;
            }
        }
        return Null ;
    }
  

    /**
    * Génère les détails d'un paiement entre wallet
    *
    * @param float $amount The amount of the payment.
    * @param Wallet $payer_wallet The wallet of the payer initiating the payment.
    * @param User  $user The user receiving the payment.
    * 
    * @return Array
    */
    private function buildInternalPaymentData(float $amount ,Wallet $payer_wallet, User $receiver): array{

        return [
            'payment' => [
                'amount' => $amount ,
                'description' => "payement done to user : ". strval($receiver->id) ." .  ". strval($receiver->name)  ,
                'payment_status_id' => 2 , // done
                'payment_method_id' => 11 , // done
                'user_id' =>  $payer_wallet->user->id ,
            ]
        ] ;
    }

    /**
     * Génère les détails d'un paiement externe.
     *
     * @param float  $amount Montant du paiement.
     * @param User   $user   Utilisateur impliqué dans le paiement.
     * @param string $type   Type de paiement : 'credit' ou autre pour débit.
     *
     * @return array Détails structurés du paiement à utiliser pour un enregistrement externe.
     */
    private function buildExternalPaymentData(float $amount ,User $user , PaymentType $type){

        return [
            'payment' => [
                'amount' => $amount,
                // 'description' =>( ($type == PaymentType::CREDIT) ? "credit made to " : " débit from"   ). " user #". strval($user->id) ." .  ". strval($user->name)." wallet" ,
                'description' =>( ($type == PaymentType::CREDIT) ? "Credit" : " Debit"   ). " for user #". strval($user->id) ." .  ". strval($user->name) ,
                'payment_status_id' => 2, // done
                'payment_method_id' => 12,
                'user_id' =>  $user->id,
            ]
        ] ;
    }



    /**
     * Crée un portefeuille (wallet) pour un utilisateur donné avec un solde initial.
     *
     * Le portefeuille est créé uniquement si une devise par défaut ($this->currency) est définie.
     *
     * @param User  $user   L'utilisateur pour lequel le portefeuille est créé.
     * @param float $amount Le solde initial du portefeuille.
     * @param string $name Nom du wallet.
     *
     * @return Wallet|null  Le portefeuille créé, ou null si la devise n'est pas définie.
     */
    private function createWallet(User $user,float $amount , $name = null ):Wallet|Null
    {
        Log::info(['function createWallet,  show xurrency',$this->currency->id ?? Null]) ;
        if (!is_null($this->currency)) {
           
            $input = [];
            $input['name'] = is_null($name) ? setting('default_wallet_name') : $name;
            $input['currency'] = $this->currency;
            $input['user_id'] = $user->id;
            $input['balance'] = $amount;
            $input['enabled'] = 1;
           return  $this->walletRepository->create($input);
        }
        return Null;
    }


    /**
     * Calcul la commission sur une transaction.
     *
     * @param float $amount Le montant de la transaction
     * @param Tax|Tax[]|null $tax paramètre pour la commission La taxe ou commission 
     * @return float Le montant de la commission
     */
    public static function getCommission(float $amount, $tax): float
    {
        $commission = 0;

        if ($tax !== null) {
            if (is_array($tax)) {
                foreach($tax as $tax_){
                    if($tax_['name'] == 'commission'){
                        if ($tax_['type'] === 'percent') {
                            $commission = ($amount * $tax_['value'] / 100);
                        } else {
                            $commission = $tax_['value'];
                        }
                    }
                }
            } else {
                if($tax['name'] == 'commission'){
                    if ($tax['type'] === 'percent') {
                        $commission = ($amount * $tax['value'] / 100);
                    } else {
                        $commission = $tax['value'];
                    }
                }
            }
        }

        // S'assurer que la commission ne dépasse pas le montant
        return min($commission, $amount);
    }

    /**
     * Extrait le TAUX de commission (en pourcentage) depuis un objet/tableau de taxes
     *
     * @param Tax|Tax[]|null $tax Le tableau ou objet de taxes
     * @return float Le taux de commission en pourcentage (ex: 5.0 pour 5%)
     */
    public static function getCommissionRate($tax): float
    {
        $rate = 0;

        if ($tax !== null) {
            if (is_array($tax)) {
                foreach($tax as $tax_){
                    if(isset($tax_['name']) && $tax_['name'] == 'commission'){
                        if (isset($tax_['type']) && $tax_['type'] === 'percent' && isset($tax_['value'])) {
                            $rate = $tax_['value'];
                        }
                    }
                }
            } else {
                if(isset($tax['name']) && $tax['name'] == 'commission'){
                    if (isset($tax['type']) && $tax['type'] === 'percent' && isset($tax['value'])) {
                        $rate = $tax['value'];
                    }
                }
            }
        }

        return $rate;
    }
}
