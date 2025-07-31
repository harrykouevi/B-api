<?php
/*
 * File name: StatusChangedPayment.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Notifications;

use App\Models\Booking;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusChangedPayment extends Notification
{
    use Queueable;

    /**
     * @var Booking
     */
    private Booking $booking;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
            ->subject(trans('lang.notification_payment', ['booking_id' => $this->booking->id, 'payment_status' => $this->booking->payment->paymentStatus->status],'fr') . " | " . setting('app_name', ''))
            ->markdown("notifications::booking", ['booking' => $this->booking])
            ->greeting(trans('lang.notification_payment', ['booking_id' => $this->booking->id, 'payment_status' => trans('lang.payment_statuses.'.$this->booking->payment->paymentStatus->status)],'fr'))
            ->action(trans('lang.booking_details'), route('bookings.show', $this->booking->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        $message = new FcmMessage();
        $notification = [
            'body' => trans('lang.notification_payment', ['booking_id' => $this->booking->id, 'payment_status' => trans('lang.payment_statuses.'.$this->booking->payment->paymentStatus->status)],'fr'),
            'title' => trans('lang.notification_status_changed_payment'),

        ];
        $data = [
            'icon' => $this->getSalonMediaUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'id' => 'App\\Notifications\\StatusChangedPayment',
            'status' => 'done',
            'bookingId' => (string) $this->booking->id,
        ];
        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);

        if ($to = $notifiable->routeNotificationFor('fcm', $this)) {
            $message->to($to);
        }
        return $message;
    }

    private function getSalonMediaUrl(): string
    {
        if ($this->booking->salon->hasMedia('image')) {
            return $this->booking->salon->getFirstMediaUrl('image', 'thumb');
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
        return [
            'booking_id' => $this->booking['id'],
        ];
    }
}
