<?php
/*
 * File name: OwnerStatusChangedBooking.php
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

class OwnerStatusChangedBooking extends Notification
{
    use Queueable;

    /**
     * @var Booking
     */
    public Booking $booking;

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
            ->markdown("notifications::booking", ['booking' => $this->booking])
            ->subject(trans('lang.notification_your_booking', ['booking_id' => $this->booking->id, 'booking_status' => $this->booking->bookingStatus->status]) . " | " . setting('app_name', ''))
            ->greeting(trans('lang.notification_your_booking', ['booking_id' => $this->booking->id, 'booking_status' => $this->booking->bookingStatus->status]))
            ->action(trans('lang.booking_details'), route('bookings.show', $this->booking->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        // Récupérer les services choisis
        $services = '';
        if ($this->booking->e_services && count($this->booking->e_services) > 0) {
            $serviceNames = [];
            foreach ($this->booking->e_services as $service) {
                $serviceNames[] = $service->name;
            }
            $services = implode(', ', $serviceNames);
        }

        // Formater l'horaire
        $horaire = '';
        if ($this->booking->booking_at) {
            $horaire = $this->booking->booking_at->format('d/m/Y H:i');
        }

        // Nom du client
        $clientName = $this->booking->user->name ?? 'Client';

        $message = new FcmMessage();
        $notification = [
            'title' => trans('lang.notification_status_changed_booking',[],'fr'),
            'body' => "RDV reporté - Client: {$clientName}, Services: {$services}, Horaire: {$horaire}",

        ];
        $data = [
            'icon' => $this->getSalonMediaUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'id' => 'App\\Notifications\\OwnerStatusChangedBooking',
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
