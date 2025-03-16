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

    public function createPayment(User $user ,float $amount ,Int|Wallet $payer_wallet ) : array | Null
    {
        $wallet = ($user->id != null) ? $this->walletRepository->findByField('user_id',  $user->id)->first() : $this->walletRepository->find(setting('app_default_wallet_id'));
        $user = $wallet->user ;
        dump(['beneficiaire',$user,$wallet]);
        $payer_wallet = (is_int($payer_wallet)) ? $this->walletRepository->find($payer_wallet) : $payer_wallet ;
        dump(['payer',$payer_wallet->user,$payer_wallet]);
        
        if($wallet== Null){
            $wallet = $this->createWallet($user , 0) ;
        }


        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
           
            $input = $this->getPaymentDetail($user,$wallet,$amount) ;
            // $input['payment']['amount'] = $amount;
            // $input['payment']['description'] = 'compte créé et crédité';
            // $input['payment']['payment_status_id'] = 2; // done
            // $input['payment']['payment_method_id'] = 11; // done
            // $input['payment']['user_id'] = $user->id;
            // $input['payment']['action'] = 'credit';
            $input['wallet']['balance'] = $wallet->balance + $amount ;
          
            $payment = $this->processPayment($input , [$wallet , $payer_wallet]) ;
            
            if($payment) $wallet =  $this->walletRepository->update(['balance'=> $wallet->balance + $amount ] , $wallet->id);
            if($payment) $payer_wallet =  $this->walletRepository->update(['balance'=> $payer_wallet->balance - $amount ] , $wallet->id);

            Notification::send([$user], new NewReceivedPayment($payment,$wallet));
            return [$payment , $wallet] ;
        }
        return Null ;
    }

    // public function debitPayment(User $user ,float $amount) : array | Null
    // {
    //     $wallet = $this->walletRepository->findByField('user_id',  $user->id)->first();
    //     if($wallet== Null){
    //         throw new Exception("no wallet for this user");
    //     }


    //     $currency = json_decode($wallet->currency, true);
    //     if ($currency['code'] == setting('default_currency_code')) {
           
    //         $input = $this->getPaymentDetail($user,$wallet,'debit',$amount) ;
    //         // $input['payment']['amount'] = $amount;
    //         // $input['payment']['description'] = 'payment de ...';
    //         // $input['payment']['payment_status_id'] = 2; // done
    //         // $input['payment']['payment_method_id'] = 11; // done
    //         // $input['payment']['user_id'] = $user->id;
    //         // $input['payment']['action'] = 'debit';
    //         // $input['wallet']['balance'] = $wallet->balance - $amount ;
          
    //         $payment = $this->processPayment($input , [$wallet , ]) ;
           
    //         if($payment) $wallet =  $this->walletRepository->update($input['wallet'] , $wallet->id);

    //         Notification::send([$user], new NewDebitPayment($payment,$wallet));
    //         return [$payment , $wallet] ;
    //     }
    //     return Null ;
    // }


    /**
     * make Payment .
     *
     * @return Payment
     */
    private function processPayment($input , array $wallets):Payment | Null
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
     *getPaymentDetail .
     *
     * @return Array
     */
    private function getPaymentDetail(User $user,Wallet $wallet,float $amount){

        $input = [];
        $input['payment']['amount'] = $amount;
        $input['payment']['description'] = 'compte créé et crédité';
        $input['payment']['payment_status_id'] = 2; // done
        $input['payment']['payment_method_id'] = 11; // done
        $input['payment']['user_id'] = $user->id;
  
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
