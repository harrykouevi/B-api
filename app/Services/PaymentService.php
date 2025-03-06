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
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewReceivedPayment;
use App\Notifications\StatusChangedPayment;

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

    public function createPayment(User $user ,float $amount)
    {
        $wallet = $this->walletRepository->findByField('user_id',  $user->id)->first();
        if($wallet== Null){
            $wallet = $this->createWallet($user , 0) ;
        }


        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
           
            $input = [];
            $input['payment']['amount'] = $amount;
            $input['payment']['description'] = 'compte créé et crédité';
            $input['payment']['payment_status_id'] = 2; // done
            $input['payment']['payment_method_id'] = 11; // done
            $input['payment']['user_id'] = $user->id;
            $input['payment']['action'] = 'credit';
            $input['wallet']['balance'] = $wallet->balance + $amount ;
            // $transaction['wallet_id'] = $wallet->id;
            // $transaction['user_id'] = $input['payment']['user_id'];
            // $transaction['amount'] = $input['payment']['amount'];
            // $transaction['description'] = $input['payment']['description'];
            // $transaction['action'] =  'credit';
            $payment = $this->processPayment($input) ;
            // $this->walletTransactionRepository->create($transaction);
            // $payment = $this->paymentRepository->create($input['payment']);
            if($payment) $wallet =  $this->walletRepository->update($input['wallet'] , $wallet->id);

            Notification::send(collect($user), new NewReceivedPayment($wallet));
        }
    }


    /**
     * make Payment .
     *
     * @return Payment
     */
    public function processPayment($input):Payment | Null
    {
        $wallet = $this->walletRepository->findByField('user_id',  $input['payment']['user_id'])->first();
        $currency = json_decode($wallet->currency, true);
        if ($currency['code'] == setting('default_currency_code')) {
           
            $transaction['wallet_id'] =  $wallet->id;
            $transaction['user_id'] = $input['payment']['user_id'];
            $transaction['amount'] = $input['payment']['amount'];
            $transaction['description'] = $input['payment']['description'];
            $transaction['action'] =  $input['payment']['action'];
            $this->walletTransactionRepository->create($transaction);
            return $payment = $this->paymentRepository->create($input['payment']);
            // Notification::send($booking->salon->users, new StatusChangedPayment($booking));
        }
        return Null ;
    }



    /**
     * make Wallet .
     *
     * @return Wallet
     */
    public function createWallet(User $user,float $amount ):Wallet|Null
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
