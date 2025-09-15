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

class StatusChangedPayment extends BaseNotification
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
        $isPurchase = $this->data instanceof Purchase;
        
        $title = trans('lang.notification_status_changed_payment', [], 'fr');
        $body = trans('lang.notification_payment', $isPurchase ? 
            ['purchase_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.strtolower($this->data->payment->paymentStatus->status))] :
            ['booking_id' => $this->data->id, 'payment_status' => trans('lang.payment_statuses.'.strtolower($this->data->payment->paymentStatus->status))], 'fr');

        // Données de base communes
        $baseData = [
            'paymentStatus' => (string)  $this->data->payment->paymentStatus->status,
            'paymentStatusName' => trans('lang.payment_statuses.'.strtolower($this->data->payment->paymentStatus->status)),
            'paymentMethod' => $this->data->payment->paymentMethod->name ?? null,
            'amount' => (string) $this->data->payment->price,
            'currency' => 'EUR',
            'paymentId' => (string) $this->data->payment->id,
            'createdAt' => (string) $this->data->payment->created_at ? \Illuminate\Support\Carbon::parse($this->data->payment->created_at)->toIso8601String() : null,
        ];

        if ($isPurchase) {
            $data = array_merge($baseData, [
                'type' => 'purchase',
                'purchaseId' => (string) $this->data->id,
                'salon' => [
                    'id' => (string) $this->data->salon->id,
                    'name' => (string) $this->data->salon->name,
                ],
                'services' => collect($this->data->e_services)->map(function($service) {
                    return [
                        'id' => (string) $service->id,
                        'name' => (string) $service->name,
                        'price' => (string) $service->price,
                    ];
                })->toArray(),
            ]);
        } else {
            $data = array_merge($baseData, [
                'type' => 'booking',
                'bookingId' => (string) $this->data->id,
                'bookingAt' => (string)  $this->data->booking_at ? \Illuminate\Support\Carbon::parse($this->data->booking_at)->toIso8601String() : null,
                'atSalon' => (string)  $this->data->at_salon,
                'salon' => json_encode( [
                    'id' => (string) $this->data->salon->id,
                    'name' => (string) $this->data->salon->name,
                    'phone' => (string) $this->data->salon->mobile_number,
                ]),
                'services' => json_encode(collect($this->data->e_services)->map(function($service) {
                    return [
                        'id' => (string) $service->id,
                        'name' => (string) $service->name,
                        'price' => (string) $service->price,
                        'duration' => (string) $service->duration ?? null,
                    ];
                })->toArray()),
            ]);
        }

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    private function getSalonMediaUrl(): string
    {
        if ($this->data->salon->hasMedia('image')) {
            return $this->data->salon->getFirstMediaUrl('image', 'thumb');
        } else {
            return asset('images/image_default.png');
        }
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
      
        if ( $this->data instanceof Purchase){
            return [
                'purchase_id' => (string) $this->data['id'],
            ];
        }
        return [
            'booking_id' => (string) $this->data['id'],
        ];
        
    }
}
