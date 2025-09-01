<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Notifications\RechargePayment;
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
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewReceivedPayment;
use App\Repositories\PaymentMethodRepository;

class PaymentService
{
    private $bookingRepository;
    private $walletRepository;
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
    * @return Array|Null
    */
    public function createPayment(float $amount ,Int|String|Wallet $payer_wallet ,User $user = new User() , WalletType $wallettype = null) : array | Null
    {
        
        $payer_wallet = ($payer_wallet instanceof Wallet ) ? $payer_wallet  : $this->walletRepository->find($payer_wallet)  ;
        
        if($user->id != null){ 
            $wallet = ($wallettype !== null) ? $this->walletRepository->findByField('user_id',  $user->id)
                                                                    ->findByField('name',  $wallettype)->first() 
                                : $this->walletRepository->findByField('user_id',  $user->id)->first() ;
        }else{
            $wallet =  $this->walletRepository->find(setting('app_default_wallet_id'));
        }

        if($wallet == Null){
            $wallet = ($wallettype == null )? $this->createWallet($user, 0) : $this->createWallet($user, 0, $wallettype);
        }

        $user = $wallet->user ;
        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
         
            if($amount != 0) { 
                try{
                    $payment = $this->toWalletFromWallet($this->getPaymentDetail($amount,$payer_wallet,$user), [$wallet , $payer_wallet]) ;
                    // Log::info(['PaymentServicee-createPayment',$wallet->user]);

                    Notification::send([$wallet->user], new NewReceivedPayment($payment,$wallet));
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }
           
            return [$payment , $wallet] ;
        }
        return Null ;
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
    * @param string $wallettype Paramètre optionnel pour le type de portefeuille
    * @return Array|Null
    */
    public function createPaymentToWallet(float $amount ,Int|String|Wallet $payer_wallet ,User $user = new User() ,  string $wallettype = null ) : array | Null
    {
        
        $payer_wallet = ($payer_wallet instanceof Wallet ) ? $payer_wallet  : $this->walletRepository->find($payer_wallet)  ;
        if($user->id != null){ 
            $wallet = ($wallettype !== null) ? $this->walletRepository->findByField('user_id',  $user->id)
                                                                    ->findByField('name',  $wallettype)->first() 
                                : $this->walletRepository->findByField('user_id',  $user->id)->first() ;
        }else{
            $wallet =  $this->walletRepository->find(setting('app_default_wallet_id'));
        }

        if($wallet == Null){
            $wallet = $this->createWallet($user , 0 , $wallettype) ;
        }


        $user = $wallet->user ;
        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
         
            if($amount != 0) { 
                try{
                    $payment = $this->toWalletFromWallet($this->getPaymentDetail($amount,$payer_wallet,$user), [$wallet , $payer_wallet]) ;
                    Log::error(['PaymentServicee-createPayment',$wallet->user]);

                    Notification::send([$wallet->user], new NewReceivedPayment($payment,$wallet));
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }
           
            return [$payment , $wallet] ;
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
     *
     * @return array|null Détails de la transaction ou null en cas d’échec.
     */
    public function createPaymentLinkWithExternal(float $amount, User|Wallet $data, PaymentType $type , string $wallettype = null): ?array
    {
        try {

            if ($data instanceof Wallet )  $wallet = $data ;
            if ($data instanceof User )  $user = $data ;
           
            if (!$wallet) {
                
                $wallet = ($wallettype == null )? $this->createWallet($user, 0) : $this->createWallet($user, 0, $wallettype);
                if (!$wallet) {
                    throw new Exception('Failed to create wallet');
                }
            }

            $currency = json_decode($wallet->currency, true);
            if ($currency['code'] !== setting('default_currency_code')) {
                return null;
            }

            if ($amount == 0) {
                return [null, $wallet]; // Retour cohérent même pour amount=0
            }

            $payment = $this->withExternalTransaction(
                $this->getWithExternalPaymentDetail($amount, $user, $type),
                $wallet,
                $type
            );

            try {
                if ($payment && $wallet->user) {
                    Notification::send([$wallet->user], new RechargePayment($payment, $wallet));
                }
            } catch (Exception $e) {
                Log::error('Notification failed: ' . $e->getMessage());
                // On continue malgré l'échec de la notification
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
     * 
     * @return Payment | Null
     */
    private function toWalletFromWallet(Array $input , array $wallets):Payment | Null
    {
        
        $wallet =  $wallets[0] ;
        $payer_wallet =  $wallets[1] ;
        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
            if($input['payment']['amount'] != 0){

                $payment = $this->paymentRepository->create($input['payment']);

                $transaction['amount'] = $input['payment']['amount'];
                $transaction['payment_id'] = $payment->id;
                
                for ($i=0; $i <= 1  ; $i++) { 
                    if($i == 0){
                        $transaction['user_id'] = $wallet->user_id;
                        $transaction['wallet_id'] = $wallet->id;
                        $transaction['description'] = 'compte credité';
                        $transaction['action'] =  'credit';
                    }
                    if($i == 1){
                        $transaction['user_id'] = $payer_wallet->user_id;
                        $transaction['wallet_id'] = $payer_wallet->id;
                        $transaction['description'] = 'compte débité';
                        $transaction['action'] =  'debit';
                    }

                    $this->walletTransactionRepository->create($transaction);
                }
                return $payment ;
            }
        }
        return Null ;
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
        
        // $wallet =  $wallets[0] ;
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
                        $transaction['amount'] = $input['payment']['amount'] - (($type == PaymentType::CREDIT)? setting('debit_fees',0)  : setting('debit_fees',0)) ;
                        $transaction['wallet_id'] = $wallet->id;
                        $transaction['description'] = ($type == PaymentType::CREDIT)? 'compte credité' : 'compte débité';
                        $transaction['action'] =  ($type == PaymentType::CREDIT)? 'credit' : 'debit';
                    }
                    if($i == 1  && ( ($type == PaymentType::CREDIT && setting('debit_fees',0) > 0) || ($type == PaymentType::DEBIT && setting('debit_fees',0) > 0) )){
                        $transaction['payment_id'] = $payment->id;
                        $transaction['user_id'] = $ptf_wallet->user_id;
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
    * @param Wallet $wallet The wallet of the payer initiating the payment.
    * @param User  $user The user receiving the payment.
    * 
    * @return Array
    */
    private function getPaymentDetail(float $amount ,Wallet $wallet, User $user){

        $input = [];
        $input['payment']['amount'] = $amount;
        $input['payment']['description'] = "payement done to user : ". strval($user->id) ." .  ". strval($user->name) ;
        $input['payment']['payment_status_id'] = 2; // done
        $input['payment']['payment_method_id'] = 11; // done
        $input['payment']['user_id'] =  $wallet->user->id;
  
        return $input;
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
    private function getWithExternalPaymentDetail(float $amount ,User $user , PaymentType $type){

        $input = [];
        $input['payment']['amount'] = $amount;
        $input['payment']['description'] =( ($type == PaymentType::CREDIT) ? "credit made to " : " débit from"   ). " user #". strval($user->id) ." .  ". strval($user->name)." wallet" ;
        $input['payment']['payment_status_id'] = 2; // done
        $input['payment']['payment_method_id'] = 12;
        $input['payment']['user_id'] =  $user->id;

        return $input;
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
}

enum PaymentType: string {
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}

enum WalletType: string {
    case PRINCIPAL = 'Igris';
    case BONUS = 'Bonus';
}
