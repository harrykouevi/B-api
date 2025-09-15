<?php

namespace App\Notifications;

use App\Models\Booking;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReportedClientNotification extends BaseNotification
{
    use Queueable;

    /**
     * @var Booking
     */
    public Booking $originalBooking;
    public Booking $newBooking;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking $originalBooking, Booking $newBooking)
    {
        $this->originalBooking = $originalBooking;
        $this->newBooking = $newBooking;
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
            ->markdown("notifications::booking_reported_client", [
                'originalBooking' => $this->originalBooking,
                'newBooking' => $this->newBooking
            ])
            ->subject(trans('lang.notification_booking_reported_client_subject', [
                'booking_id' => $this->originalBooking->id
            ]) . " | " . setting('app_name', ''))
            ->greeting(trans('lang.notification_booking_reported_client_greeting'))
            ->line(trans('lang.notification_booking_reported_client_line1', [
                'booking_id' => $this->originalBooking->id
            ]))
            ->line(trans('lang.notification_booking_reported_client_line2', [
                'new_date' => $this->newBooking->booking_at->format('d/m/Y'),
                'new_time' => $this->newBooking->booking_at->format('H:i')
            ]))
            ->action(trans('lang.booking_details'), route('bookings.show', $this->newBooking->id));
    }

    public function toFcm($notifiable): FcmMessage
    {
        // Format services
        $services = '';
        if ($this->newBooking->e_services && count($this->newBooking->e_services) > 0) {
            $serviceNames = [];
            foreach ($this->newBooking->e_services as $service) {
                $serviceNames[] = $service->name;
            }
            $services = implode(', ', $serviceNames);
        }

        // Format booking date and time
        $newDate = $this->newBooking->booking_at->format('d/m/Y');
        $newTime = $this->newBooking->booking_at->format('H:i');

        $title = trans('lang.notification_booking_reported_client_title');
        $body = trans('lang.notification_booking_reported_client_body', [
            'booking_id' => $this->originalBooking->id,
            'new_date' => $newDate,
            'new_time' => $newTime,
            'services' => $services
        ]);

        $data = [
            'originalBookingId' => (string) $this->originalBooking->id,
            'newBookingId' => (string) $this->newBooking->id,
            'reportType' => 'booking_rescheduled',
            'originalBookingAt' => $this->originalBooking->booking_at ? \Illuminate\Support\Carbon::parse($this->originalBooking->booking_at)->toIso8601String() : null,
            'newBookingAt' => $this->newBooking->booking_at ? \Illuminate\Support\Carbon::parse($this->newBooking->booking_at)->toIso8601String() : null,
            'atSalon' => $this->newBooking->at_salon,
            'totalPrice' => (string) $this->newBooking->total,
            'salon' => [
                'id' => (string) $this->newBooking->salon->id,
                'name' => $this->newBooking->salon->name,
                'phone' => $this->newBooking->salon->mobile_number,
                'address' => $this->newBooking->salon->address,
            ],
            'services' => collect($this->newBooking->services)->map(function($service) {
                return [
                    'id' => (string) $service->id,
                    'name' => $service->name,
                    'price' => (string) $service->price,
                    'duration' => $service->duration ?? null,
                ];
            })->toArray(),
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    protected function getIconUrl(): string
    {
        if ($this->newBooking->salon->hasMedia('image')) {
            return $this->newBooking->salon->getFirstMediaUrl('image', 'thumb');
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
            'original_booking_id' => $this->originalBooking->id,
            'new_booking_id' => $this->newBooking->id,
            'type' => 'booking_reported_client'
        ];
    }
}