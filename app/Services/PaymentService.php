<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletTransactionRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewReceivedPayment;
use App\Notifications\NewDebitPayment ;
use App\Notifications\StatusChangedPayment;
use PhpParser\Node\Expr\Cast\Double;

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
        $this->currency = $this->currencyRepository->find(setting('default_currency_id'));
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
    * @return Array|Null
    */
    public function createPayment(float $amount ,Int|String|Wallet $payer_wallet ,User $user = new User() ) : array | Null
    {
        
        $payer_wallet = ($payer_wallet instanceof Wallet ) ? $payer_wallet  : $this->walletRepository->find($payer_wallet)  ;

        $wallet = ($user->id != null) ? $this->walletRepository->findByField('user_id',  $user->id)->first() : $this->walletRepository->find(setting('app_default_wallet_id'));
        if($wallet == Null){
            $wallet = $this->createWallet($user , 0) ;
        }
        $user = $wallet->user ;

       


        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
           
            $payment = $this->processPayment($this->getPaymentDetail($amount,$payer_wallet,$user), [$wallet , $payer_wallet]) ;
            dd($user ) ;
            if($payment) $wallet =  $this->walletRepository->update(['balance'=> $wallet->balance + $amount ] , $wallet->id);
            if($payment) $payer_wallet =  $this->walletRepository->update(['balance'=> $payer_wallet->balance - $amount ] , $wallet->id);

            Notification::send([$user], new NewReceivedPayment($payment,$wallet));
            return [$payment , $wallet] ;
        }
        return Null ;
    }

    


    /**
     * make Payment .
     * @param Array $input
     * @param Array $wallets
     * 
     * @return Payment | Null
     */
    private function processPayment(Array $input , array $wallets):Payment | Null
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
    * getPaymentDetail .
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
        $input['payment']['description'] = "payement done to user : ". strval($user->id) .". Compte dédité";
        $input['payment']['payment_status_id'] = 2; // done
        $input['payment']['payment_method_id'] = 11; // done
        $input['payment']['user_id'] =  $wallet->user->id;
  
        return $input;
    }



    /**
     * make Wallet .
     *
     * @return Wallet
     */
    private function createWallet(User $user,float $amount ):Wallet|Null
    {
        $currency = $this->currency;
        if ($currency) {
            
            $input = [];
            $input['name'] = setting('default_wallet_name')?? "-";
            $input['currency'] = $currency;
            $input['user_id'] = $user->id;
            $input['balance'] = $amount;
            $input['enabled'] = 1;
           return  $this->walletRepository->create($input);
        }
        return Null;
    }
}
