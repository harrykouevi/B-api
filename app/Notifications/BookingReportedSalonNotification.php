<?php

namespace App\Notifications;

use App\Models\Booking;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReportedSalonNotification extends BaseNotification
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

        // Client name
        $clientName = $this->originalBooking->user->name ?? 'Client';

        return (new MailMessage)
            ->markdown("notifications::booking_reported_salon", [
                'originalBooking' => $this->originalBooking,
                'newBooking' => $this->newBooking
            ])
            ->subject(trans('lang.notification_booking_reported_salon_subject', [
                'booking_id' => $this->originalBooking->id,
                'client_name' => $clientName
            ]) . " | " . setting('app_name', ''))
            ->greeting(trans('lang.notification_booking_reported_salon_greeting'))
            ->line(trans('lang.notification_booking_reported_salon_line1', [
                'client_name' => $clientName,
                'booking_id' => $this->originalBooking->id
            ]))
            ->line(trans('lang.notification_booking_reported_salon_line2', [
                'services' => $services,
                'new_date' => $newDate,
                'new_time' => $newTime
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

        // Client name
        $clientName = $this->originalBooking->user->name ?? 'Client';

        $title = trans('lang.notification_booking_reported_salon_title');
        $body = trans('lang.notification_booking_reported_salon_body', [
            'client_name' => $clientName,
            'booking_id' => $this->originalBooking->id,
            'services' => $services,
            'new_date' => $newDate,
            'new_time' => $newTime
        ]);

        $data = [
            'originalBookingId' => (string) $this->originalBooking->id,
            'newBookingId' => (string) $this->newBooking->id,
            'reportType' => 'booking_rescheduled',
            'originalBookingAt' => $this->originalBooking->booking_at ? \Illuminate\Support\Carbon::parse($this->originalBooking->booking_at)->toIso8601String() : null,
            'newBookingAt' => $this->newBooking->booking_at ? \Illuminate\Support\Carbon::parse($this->newBooking->booking_at)->toIso8601String() : null,
            'atSalon' => (string) $this->newBooking->at_salon,
            'totalPrice' => (string) $this->newBooking->total,
            'client' => json_encode([
                'id' => (string) $this->originalBooking->user->id,
                'name' => $clientName,
                'phone' => (string) $this->originalBooking->user->phone_number,
                'email' => $this->originalBooking->user->email,
            ]),
            'services' => json_encode(collect($this->newBooking->e_services)->map(function($service) {
                return [
                    'id' => (string) $service->id,
                    'name' => $service->name,
                    'price' => (string) $service->price,
                    'duration' => (string) $service->duration ?? null,
                ];
            })->toArray()),
            'address' => $this->newBooking->address ?json_encode( [
                'description' => $this->newBooking->address->description,
                'latitude' => (string) $this->newBooking->address->latitude,
                'longitude' => (string) $this->newBooking->address->longitude,
            ]) : null,
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
            'client_name' => $this->originalBooking->user->name,
            'type' => 'booking_reported_salon'
        ];
    }
}