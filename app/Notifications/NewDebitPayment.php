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

class NewReceivedPayment extends Notification
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
            ->subject(trans('lang.notification_payment', ['payment_id' => $this->payment->id, 'payment_status' => $this->payment->paymentStatus->status]) . " | " . setting('app_name', ''))
            ->markdown("notifications::wallet", ['wallet' => $this->wallet])
            ->greeting(trans('lang.notification_payment', ['payment_id' => $this->payment->id, 'payment_status' => $this->payment->paymentStatus->status]))
            ->action(trans('lang.wallet_details'), route('wallets.show', $this->wallet->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $message = new FcmMessage();
        $notification = [
            'body' => trans('lang.notification_payment', ['payment_id' => $this->payment->id, 'payment_status' => $this->payment->paymentStatus->status],'fr'),
            'title' => trans('lang.notification_status_changed_payment',[],'fr'),

        ];
        $data = [
            'icon' => $this->getSalonMediaUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'id' => 'App\\Notifications\\StatusChangedPayment',
            'status' => 'done',
            'walletId' => (string) $this->wallet->id,
        ];
        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);

        if ($to = $notifiable->routeNotificationFor('fcm', $this)) {
            $message->to($to);
        }
        return $message;
    }

    private function getSalonMediaUrl(): string
    {
        
            return asset('images/image_default.png');
        
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
