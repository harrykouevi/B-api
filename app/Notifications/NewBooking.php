<?php
/*
 * File name: NewBooking.php
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

class NewBooking extends BaseNotification
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
        //
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

        return (new MailMessage)
            ->markdown("notifications::booking", ['booking' => $this->booking])
            ->subject(trans('lang.notification_new_booking', ['booking_id' => $this->booking->id, 'user_name' => $this->booking->user->name]) . " | " . setting('app_name', ''))
            ->greeting(trans('lang.notification_new_booking', ['booking_id' => $this->booking->id, 'user_name' => $this->booking->user->name]))
            ->line(trans('lang.notification_new_booking_details', [
                'booking_id' => $this->booking->id,
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

        $title = trans('lang.notification_new_booking_title', ['booking_id' => $this->booking->id]);
        $body = trans('lang.notification_new_booking_body', [
            'booking_id' => $this->booking->id,
            'user_name' => $this->booking->user->name,
            'services' => $services,
            'booking_date' => $bookingDateTime
        ]);

        // Données différenciées selon le type de destinataire
        $baseData = [
            'bookingId' => (string) $this->booking->id,
            'bookingStatus' => $this->booking->bookingStatus->status,
            'bookingStatusOrder' => (string) $this->booking->bookingStatus->order,
            'bookingAt' => $this->booking->booking_at ? \Illuminate\Support\Carbon::parse($this->booking->booking_at)->toIso8601String() : null,
            'atSalon' => (string) $this->booking->at_salon,
            'totalPrice' => (string) $this->booking->total,
            'services' => collect($this->booking->e_services)->map(function($service) {
                return [
                    'id' => (string) $service->id,
                    'name' => $service->name,
                    'price' => (string) $service->price,
                    'duration' => (string) $service->duration ?? null,
                ];
            })->toArray(),
        ];

        // Données spécifiques selon le destinataire
        if ($notifiable->hasRole('salon owner')) {
            // Pour les salons : informations client
            $data = array_merge($baseData, [
                'client' => [
                    'id' => (string) $this->booking->user->id,
                    'name' => $this->booking->user->name,
                    'phone' => (string) $this->booking->user->phone_number,
                    'email' => $this->booking->user->email,
                ],
                'address' => $this->booking->address ? [
                    'description' => $this->booking->address->description,
                    'latitude' => (string) $this->booking->address->latitude,
                    'longitude' => (string) $this->booking->address->longitude,
                ] : null,
            ]);
        } else {
            // Pour les clients : informations salon
            $data = array_merge($baseData, [
                'salon' => [
                    'id' => (string) $this->booking->salon->id,
                    'name' => $this->booking->salon->name,
                    'phone' => (string) $this->booking->salon->mobile_number,
                    'address' => $this->booking->salon->address,
                ],
            ]);
        }

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
