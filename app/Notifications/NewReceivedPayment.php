<?php
/*
 * File name: StatusChangedWallettransaction.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Notifications;

use App\Models\Wallettransaction;
use App\Models\Wallet;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReceivedPayment extends BaseNotification
{
    use Queueable;

    /**
     * @var Wallet
     */
    // private Wallet $wallet;

     /**
     * @var Wallettransaction
     */
    private Wallettransaction $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Wallettransaction $transaction)
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
            ->subject(trans('lang.notification_payment', ['payment_id' => $this->transaction->payment->id, 'payment_status' => $this->transaction->payment->paymentStatus->status],'fr') . " | " . setting('app_name', ''))
            // ->markdown("notifications::wallet", ['wallet' => $this->transaction->wallet])
            ->greeting(trans('lang.notification_payment', ['payment_id' => $this->transaction->payment->id, 'payment_status' => $this->transaction->payment->paymentStatus->status],'fr'))
            ->action(trans('lang.wallet_details'), route('wallets.show', $this->transaction->wallet->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $title = trans('lang.notification_status_changed_payment',[],'fr');
        $body = trans('lang.notification_payment', [
            'payment_id' => $this->transaction->payment->id, 
            'payment_status' => trans('lang.payment_statuses.'.$this->transaction->payment->paymentStatus->status,[],'fr'), 
            'payment_amount' => $this->transaction->payment->amount
        ],'fr');

        $data = [
            'walletId' => (string) $this->transaction->wallet->id,
            'transactionId' => (string) $this->transaction->id,
            'paymentId' => (string) $this->transaction->payment->id,
            'amount' => (string) $this->transaction->amount,
            'paymentStatus' => $this->transaction->payment->paymentStatus->status,
            'paymentMethod' => $this->transaction->payment->paymentMethod->name ?? null,
            'transactionType' => $this->transaction->action,
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
            'wallet_id' => $this->transaction->wallet['id'],
        ];
    }
}
