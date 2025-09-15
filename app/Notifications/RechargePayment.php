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
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RechargePayment extends BaseNotification
{
    use Queueable;

    /**
     * @var Wallet
     */
    private Wallet $wallet;

     /**
     * @var Payment
     */
    private Payment $payment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Payment $payment,Wallet $wallet)
    {
        $this->wallet = $wallet;
        $this->payment = $payment;
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
            ->subject(trans('lang.notification_recharge', ['payment_id' => $this->payment->id, 'payment_status' => $this->payment->paymentStatus->status], 'fr') . " | " . setting('app_name', ''))
            ->markdown("notifications::wallet", ['wallet' => $this->wallet])
            ->greeting(trans('lang.notification_recharge', ['payment_id' => $this->payment->id, 'payment_status' => trans('lang.payment_statuses.'.$this->payment->paymentStatus->status)],'fr'))
            ->action(trans('lang.wallet_details'), route('wallets.show', $this->wallet->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $title = trans('lang.notification_status_changed_payment', [], 'fr');
        $body = trans('lang.notification_recharge', [
            'payment_id' => $this->payment->id, 
            'payment_status' => trans('lang.payment_statuses.'.$this->payment->paymentStatus->status, [], 'fr'),
            'payment_amount' => $this->payment->amount
        ], 'fr');

        $data = [
            'type' => 'wallet_recharge',
            'walletId' => (string) $this->wallet->id,
            'paymentId' => (string) $this->payment->id,
            'paymentStatus' => $this->payment->paymentStatus->status,
            'paymentStatusName' => trans('lang.payment_statuses.'.$this->payment->paymentStatus->status),
            'paymentMethod' => (string) $this->payment->paymentMethod->name ?? null,
            'amount' => (string) $this->payment->price,
            'currency' => 'EUR',
            'previousBalance' => (string) ($this->wallet->balance - $this->payment->price),
            'newBalance' => (string) $this->wallet->balance,
            'createdAt' => $this->payment->created_at ? \Illuminate\Support\Carbon::parse($this->payment->created_at)->toIso8601String() : null,
            'user' => [
                'id' => (string) $this->wallet->user->id,
                'name' => $this->wallet->user->name,
                'email' => $this->wallet->user->email,
            ],
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
            'wallet_id' => $this->wallet['id'],
        ];
    }
}
