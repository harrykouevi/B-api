<?php
/*
 * File name: StatusChangedPayment.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Purchase;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusChangedPayment extends Notification
{
    use Queueable;

    /**
     * @var Booking|Purchase
     */
    private Booking|Purchase $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking|Purchase $data)
    {
        $this->data = $data;
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
        if ( $this->data instanceof Purchase){
            return (new MailMessage)
                ->subject(trans('lang.notification_payment', ['purchase_id' => $this->data->id, 'payment_status' => $this->data->payment->paymentStatus->status],'fr') . " | " . setting('app_name', ''))
                ->markdown("notifications::purchase", ['purchase' => $this->data])
                ->greeting(trans('lang.notification_payment', ['purchase_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.$this->data->payment->paymentStatus->status)],'fr'))
                // ->action(trans('lang.purchase_details'), route('purchases.show', $this->data->id))
                ;
        }
        return (new MailMessage)
            ->subject(trans('lang.notification_payment', ['booking_id' => $this->data->id, 'payment_status' => $this->data->payment->paymentStatus->status],'fr') . " | " . setting('app_name', ''))
            ->markdown("notifications::booking", ['booking' => $this->data])
            ->greeting(trans('lang.notification_payment', ['booking_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.$this->data->payment->paymentStatus->status)],'fr'))
            ->action(trans('lang.booking_details'), route('bookings.show', $this->data->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $message = new FcmMessage();
        $notification = [
            'body' =>  trans('lang.notification_payment',  ( $this->data instanceof Purchase)? 
                            ['purchase_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.strtolower($this->data->payment->paymentStatus->status))] :
                            ['booking_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.strtolower($this->data->payment->paymentStatus->status))],'fr'),
            'title' => trans('lang.notification_status_changed_payment',[],'fr'),

        ];
        $data = [
            'icon' => $this->getSalonMediaUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'id' => 'App\\Notifications\\StatusChangedPayment',
            'status' => 'done',
        ];
        if($this->data instanceof Purchase) $data['purchaseId'] = (string) $this->data->id ;
        if($this->data instanceof Booking) $data['bookingId'] = (string) $this->data->id ;

        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);

        if ($to = $notifiable->routeNotificationFor('fcm', $this)) {
            $message->to($to);
        }
        return $message;
    }

    private function getSalonMediaUrl(): string
    {
        if ($this->data->salon->hasMedia('image')) {
            return $this->data->salon->getFirstMediaUrl('image', 'thumb');
        } else {
            return asset('images/image_default.png');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
      
        if ( $this->data instanceof Purchase){
            return [
                'purchase_id' => $this->data['id'],
            ];
        }
        return [
            'booking_id' => $this->data['id'],
        ];
        
    }
}
