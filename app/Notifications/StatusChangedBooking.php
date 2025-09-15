<?php
/*
 * File name: StatusChangedBooking.php
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

class StatusChangedBooking extends BaseNotification
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
        // Format services
        $services = '';
        if ($this->booking->e_services && count($this->booking->e_services) > 0) {
            $serviceNames = [];
            foreach ($this->booking->e_services as $service) {
                $serviceNames[] = $service->name;
            }
            $services = implode(', ', $serviceNames);
        }

        // Format booking date and time
        $bookingDateTime = '';
        if ($this->booking->booking_at) {
            $bookingDateTime = $this->booking->booking_at->format('d/m/Y à H:i');
        }

        $bookingStatus = trans('lang.booking_statuses.' . $this->booking->bookingStatus->status);

        return (new MailMessage)
            ->markdown("notifications::booking", ['booking' => $this->booking])
            ->subject(trans('lang.notification_status_changed_client_subject', ['booking_id' => $this->booking->id, 'booking_status' => $bookingStatus]) . " | " . setting('app_name', ''))
            ->greeting(trans('lang.notification_status_changed_client_greeting'))
            ->line(trans('lang.notification_status_changed_client_line1', ['booking_id' => $this->booking->id]))
            ->line(trans('lang.notification_status_changed_client_line2', [
                'booking_status' => $bookingStatus,
                'services' => $services,
                'booking_date' => $bookingDateTime
            ]))
            ->action(trans('lang.booking_details'), route('bookings.show', $this->booking->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        // Format services
        $services = '';
        if ($this->booking->e_services && count($this->booking->e_services) > 0) {
            $serviceNames = [];
            foreach ($this->booking->e_services as $service) {
                $serviceNames[] = $service->name;
            }
            $services = implode(', ', $serviceNames);
        }

        // Format booking date and time
        $bookingDateTime = '';
        if ($this->booking->booking_at) {
            $bookingDateTime = $this->booking->booking_at->format('d/m/Y à H:i');
        }

        $bookingStatus = trans('lang.booking_statuses.' . $this->booking->bookingStatus->status);

        $title = trans('lang.notification_status_changed_client_title');
        $body = trans('lang.notification_status_changed_client_body', [
            'booking_id' => $this->booking->id,
            'booking_status' => $bookingStatus,
            'services' => $services,
            'booking_date' => $bookingDateTime
        ]);

        $data = [
            'bookingId' => (string) $this->booking->id,
            'bookingStatus' => (string) $this->booking->bookingStatus->status,
            'bookingStatusOrder' => (string) $this->booking->bookingStatus->order,
            'bookingStatusName' => (string) $bookingStatus,
            'bookingAt' => (string) $this->booking->booking_at ? \Illuminate\Support\Carbon::parse($this->booking->booking_at)->toIso8601String() : null,
            'atSalon' => (string) $this->booking->at_salon,
            'totalPrice' => (string) $this->booking->total,
            'salon' => json_encode([
                'id' => (string) $this->booking->salon->id,
                'name' => (string)  $this->booking->salon->name,
                'phone' => (string)  $this->booking->salon->mobile_number,
                'address' => (string)  $this->booking->salon->address,
            ]),
            'services' => json_encode(collect($this->booking->e_services)->map(function($service) {
                return [
                    'id' => (string) $service->id,
                    'name' => $service->name,
                    'price' => (string) $service->price,
                    'duration' => (string) $service->duration ?? null,
                ];
            })->toArray()),
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    protected function getIconUrl(): string
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
