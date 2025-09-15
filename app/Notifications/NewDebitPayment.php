<?php
/*
 * File name: StatusChangedPayment.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDebitPayment extends BaseNotification
{
    use Queueable;

    /**
     * @var Wallet
     */
    // private Wallet $wallet;

     /**
     * @var WalletTransaction
     */
    private WalletTransaction $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(WalletTransaction $transaction)
    {
        // $this->transaction->wallet = $wallet;
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        $types = ['database'];
        if (setting('enable_notifications', false)) {
            $types[] = 'fcm';
        }
        if (setting('enable_email_notifications', false)) {
            $types[] = 'mail';
        }
        return $types;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('lang.notification_payment', ['payment_id' => $this->transaction->payment_id, 'payment_status' => $this->transaction->payment->paymentStatus->status]) . " | " . setting('app_name', ''))
            // ->markdown("notifications::wallet", ['wallet' => $this->transaction->wallet])
            ->greeting(trans('lang.notification_payment', ['payment_id' => $this->transaction->payment_id, 'payment_status' => $this->transaction->payment->paymentStatus->status]))
            ->action(trans('lang.wallet_details'), route('wallets.show', $this->transaction->wallet->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $title = trans('lang.notification_status_changed_payment', [], 'fr');
        $body = trans('lang.notification_payment', [
            'payment_id' => $this->transaction->payment_id, 
            'payment_status' => $this->transaction->payment->paymentStatus->status
        ], 'fr');

        $data = [
            'type' => 'wallet_debit',
            'transactionId' => (string) $this->transaction->id,
            'walletId' => (string) $this->transaction->wallet->id,
            'paymentId' => (string) $this->transaction->payment_id,
            'paymentStatus' => (string) $this->transaction->payment->paymentStatus->status,
            'paymentStatusName' => trans('lang.payment_statuses.'.$this->transaction->payment->paymentStatus->status),
            'paymentMethod' => $this->transaction->payment->paymentMethod->name ?? null,
            'amount' => (string) $this->transaction->amount,
            'currency' => 'EUR',
            'action' => $this->transaction->action, // 'debit' ou 'credit'
            'description' => $this->transaction->description ?? null,
            'previousBalance' => (string) ($this->transaction->wallet->balance + $this->transaction->amount),
            'newBalance' => (string) $this->transaction->wallet->balance,
            'createdAt' => $this->transaction->created_at ? \Illuminate\Support\Carbon::parse($this->transaction->created_at)->toIso8601String() : null,
            'user' => json_encode([
                'id' => (string) $this->transaction->wallet->user->id,
                'name' => $this->transaction->wallet->user->name,
                'email' => $this->transaction->wallet->user->email,
            ]),
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    private function getSalonMediaUrl(): string
    {
        return asset('images/image_default.png');
    }

    protected function getIconUrl(): string
    {
        return $this->getSalonMediaUrl();
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return [
           
        ];
    }
}
