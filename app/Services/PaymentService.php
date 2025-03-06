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
use App\Repositories\WalletTransactionRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Notifications\NewReceivedPayment;
use App\Notifications\StatusChangedPayment;

class PaymentService
{
    private $bookingRepository;
    private $walletRepository;
    private $walletTransactionRepository;
    private $paymentRepository;
    

    public function __construct(
        BookingRepository $bookingRepository,
        WalletRepository $walletRepository,
        WalletTransactionRepository $walletTransactionRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->walletRepository = $walletRepository;
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function createPayment(float $amount)
    {
        $wallet = $this->walletRepository->findByField('user_id',  auth()->id());

        if ($wallet->currency->code == setting('default_currency_code')) {
            $input['payment']['amount'] = $amount;
            $input['payment']['description'] = 'compte créé et crédité';
            $input['payment']['payment_status_id'] = 2; // done
            $input['payment']['user_id'] = Auth::id();
            $transaction['wallet_id'] = $wallet->id;
            $transaction['user_id'] = $input['payment']['user_id'];
            $transaction['amount'] = $input['payment']['amount'];
            $transaction['description'] = $input['payment']['description'];
            $transaction['action'] =  'credit';
            $this->walletTransactionRepository->create($transaction);
            $payment = $this->paymentRepository->create($input['payment']);
            Notification::send(collect(auth()->id()), new NewReceivedPayment($wallet));
        }
    }


    /**
     * make Payment .
     *
     * @return Payment
     */
    public function processPayment($input):Payment | Null
    {
        $wallet = $this->walletRepository->findByField('user_id',  auth()->id());

        if ($wallet->currency->code == setting('default_currency_code')) {
            // $input['payment']['amount'] = $booking->getTotal();
            // $input['payment']['description'] = __('lang.payment_booking_id') . $input['id'];
            // $input['payment']['payment_status_id'] = 2; // done
            // $input['payment']['user_id'] = Auth::id();
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
}
