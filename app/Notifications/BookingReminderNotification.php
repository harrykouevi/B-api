<?php

namespace App\Notifications;

use App\Models\Booking;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BookingReminderNotification extends Notification
{
    use Queueable;

    private Booking $booking;
    private string $reminderType;
    private string $recipient; // 'client' or 'salon'

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $reminderType, string $recipient = 'client')
    {
        $this->booking = $booking;
        $this->reminderType = $reminderType;
        $this->recipient = $recipient;
    }

    /**
     * Get the notification's delivery channels.
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
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = $this->getEmailSubject();
        $greeting = $this->getEmailGreeting();
        $actionText = $this->recipient === 'client' ? 'Voir ma réservation' : 'Gérer la réservation';
        $actionUrl = route('bookings.show', $this->booking->id);

        $mailMessage = (new MailMessage)
            ->markdown("notifications::booking_reminder", [
                'booking' => $this->booking,
                'reminderType' => $this->reminderType,
                'recipient' => $this->recipient,
                'services' => $this->getDetailedServices(),
                'timeUntil' => $this->getTimeUntilAppointment(),
                'locationDetails' => $this->getLocationDetails(),
                'priceDetails' => $this->getPriceDetails(),
            ])
            ->subject($subject)
            ->greeting($greeting)
            ->action($actionText, $actionUrl);

        // Ajouter des détails dans le corps de l'email
        if ($this->recipient === 'client') {
            $mailMessage = $this->addClientEmailDetails($mailMessage);
        } else {
            $mailMessage = $this->addSalonEmailDetails($mailMessage);
        }

        return $mailMessage;
    }

    public function getData(): Array{
        return [
            'id' => 'App\\Notifications\\BookingReminderNotification',
            'icon' => $this->getSalonMediaUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'status' => 'reminder',
            'bookingId' => (string) $this->booking->id,
            'reminderType' => (string) $this->reminderType,
            'recipient' => (string) $this->recipient,
            
            // Données enrichies pour l'app mobile
            'booking_date' => Carbon::parse($this->booking->booking_at)->format('Y-m-d'),
            'booking_time' => Carbon::parse($this->booking->booking_at)->format('H:i'),
            'salon_name' => (string) $this->booking->salon->name ?? '',
            'salon_id' => (string) ($this->booking->salon->id ?? ''),
            'services_count' => (string) count($this->booking->e_services ?? []),
            'total_price' => (string) number_format($this->booking->getTotal(), 2),
            'location_type' => (string) $this->booking->at_salon ? 'salon' : 'home',
            'time_until_hours' => (string) $this->getTimeUntilAppointment()['total_hours'],
            
            // Pour navigation directe
            'deep_link' => (string) $this->getDeepLink('booking', $this->booking->id),
            'salon_phone' => (string) $this->booking->salon->phone_number ?? '',
            'client_name' => (string) $this->recipient === 'salon' ? $this->booking->user->name : '',
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): FcmMessage
    {
        $message = new FcmMessage();
        $notification = [
            'title' => $this->getFcmTitle(),
            'body' => $this->getFcmBody(),
        ];

        $data = $this->getData();

        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);

        if ($to = $notifiable->routeNotificationFor('fcm', $this)) {
            $message->to($to);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(mixed $notifiable): array
    {
        $bookingAt = Carbon::parse($this->booking->booking_at);
        $createdAt = Carbon::parse($this->booking->created_at);
        
        return [
            // Informations de base
            'booking_id' => $this->booking->id,
            'reminder_type' => $this->reminderType,
            'recipient' => $this->recipient,
            
            // Informations temporelles détaillées
            'booking_date' => $bookingAt->format('Y-m-d'),
            'booking_time' => $bookingAt->format('H:i'),
            'booking_datetime' => $bookingAt->format('Y-m-d H:i:s'),
            'booking_day_name' => $bookingAt->locale('fr')->dayName,
            'booking_created_at' => $createdAt->format('Y-m-d H:i:s'),
            'time_until_appointment' => $this->getTimeUntilAppointment(),
            
            // Informations sur les services
            'services' => $this->getDetailedServices(),
            'options' => $this->getDetailedOptions(),
            'total_duration' => $this->booking->duration . ' heures',
            
            // Informations financières
            'subtotal' => number_format($this->booking->getSubtotal(), 2) . ' €',
            'taxes_amount' => number_format($this->booking->getTaxesValue(), 2) . ' €',
            'coupon_discount' => $this->booking->coupon ? number_format($this->booking->getCouponValue(), 2) . ' €' : null,
            'total_price' => number_format($this->booking->getTotal(), 2) . ' €',
            
            // Informations sur le salon
            'salon' => [
                'id' => $this->booking->salon->id ?? null,
                'name' => $this->booking->salon->name ?? '',
                'phone' => $this->booking->salon->phone_number ?? null,
                'address' => $this->getFormattedAddress(),
                'image_url' => $this->getSalonMediaUrl(),
            ],
            
            // Informations sur le client (visible pour le salon)
            'client' => $this->recipient === 'salon' ? [
                'id' => $this->booking->user->id,
                'name' => $this->booking->user->name,
                'email' => $this->booking->user->email,
                'phone' => $this->booking->user->phone_number ?? null,
            ] : null,
            
            // Informations sur l'employé assigné
            'employee' => $this->booking->employee ? [
                'id' => $this->booking->employee->id,
                'name' => $this->booking->employee->name,
            ] : null,
            
            // Détails logistiques
            'location_type' => $this->booking->at_salon ? 'au_salon' : 'a_domicile',
            'location_address' => $this->booking->at_salon ? 
                $this->getFormattedAddress() : 
                $this->getFormattedCustomerAddress(),
            
            // Statut et suivi
            'booking_status' => [
                'id' => $this->booking->bookingStatus->id ?? null,
                'name' => $this->booking->bookingStatus->status ?? 'Inconnu',
                'order' => $this->booking->bookingStatus->order ?? null,
            ],
            
            // Informations de paiement
            'payment_status' => $this->booking->payment ? [
                'method' => $this->booking->payment->payment_method->name ?? 'Non définie',
                'status' => $this->booking->payment->payment_status->status ?? 'En attente',
            ] : null,
            
            // Informations spéciales
            'special_notes' => $this->booking->hint ?? null,
            'is_reported' => $this->booking->isReported(),
            'quantity' => $this->booking->quantity,
            
            // URLs pour actions rapides (mobile)
            'deep_links' => [
                'view_booking' => $this->getDeepLink('booking', $this->booking->id),
                'contact_salon' => $this->getDeepLink('salon', $this->booking->salon->id ?? null),
                'directions' => $this->getDirectionsLink(),
            ],
            
            // Métadonnées de notification
            'notification_sent_at' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Get email subject based on reminder type
     */
    private function getEmailSubject(): string
    {
        $salonName = $this->booking->salon->name ?? 'votre salon';
        
        return match($this->reminderType) {
            'confirmation' => "✅ Confirmation de votre réservation chez {$salonName}",
            '24h' => "⏰ Rappel : Votre rendez-vous demain chez {$salonName}",
            '3h' => "⏰ Rappel : Votre rendez-vous dans 3h chez {$salonName}",
            '30min' => "🔔 Rappel : Votre rendez-vous dans 30 minutes chez {$salonName}",
            '15min' => "🚨 Rappel : Votre rendez-vous dans 15 minutes chez {$salonName}",
            default => "📅 Rappel de votre réservation chez {$salonName}"
        };
    }

    /**
     * Get email greeting based on reminder type and recipient
     */
    private function getEmailGreeting(): string
    {
        $userName = $this->recipient === 'client' ? $this->booking->user->name : 'Équipe du salon';
        $salonName = $this->booking->salon->name ?? 'votre salon';
        $bookingTime = Carbon::parse($this->booking->booking_at)->format('d/m/Y à H:i');
        
        if ($this->recipient === 'salon') {
            return match($this->reminderType) {
                '24h' => "👋 Bonjour ! Rappel : Vous avez un rendez-vous prévu demain ({$bookingTime}) avec {$this->booking->user->name}",
                '3h' => "⏰ Rappel : Vous avez un rendez-vous dans 3h ({$bookingTime}) avec {$this->booking->user->name}",
                '30min' => "🔔 Attention : Vous avez un rendez-vous dans 30 minutes ({$bookingTime}) avec {$this->booking->user->name}",
                '15min' => "🚨 Urgent : Vous avez un rendez-vous dans 15 minutes ({$bookingTime}) avec {$this->booking->user->name}",
                default => "📅 Rappel de rendez-vous avec {$this->booking->user->name}"
            };
        }

        return match($this->reminderType) {
            'confirmation' => "✅ Bonjour {$userName}, votre réservation chez {$salonName} a bien été confirmée pour le {$bookingTime}",
            '24h' => "👋 Bonjour {$userName}, nous vous rappelons votre rendez-vous prévu demain ({$bookingTime}) chez {$salonName}",
            '3h' => "⏰ Bonjour {$userName}, votre rendez-vous chez {$salonName} est dans 3h ({$bookingTime})",
            '30min' => "🔔 Bonjour {$userName}, votre rendez-vous chez {$salonName} est dans 30 minutes ({$bookingTime})",
            '15min' => "🚨 Bonjour {$userName}, votre rendez-vous chez {$salonName} est dans 15 minutes ({$bookingTime})",
            default => "📅 Bonjour {$userName}, rappel de votre réservation chez {$salonName}"
        };
    }

    /**
     * Get FCM title
     */
    private function getFcmTitle(): string
    {
        return match($this->reminderType) {
            'confirmation' => "✅ Réservation confirmée",
            '24h' => "⏰ Rendez-vous demain",
            '3h' => "⏰ Rendez-vous dans 3h",
            '30min' => "🔔 Rendez-vous dans 30 min",
            '15min' => "🚨 Rendez-vous dans 15 min",
            default => "📅 Rappel de rendez-vous"
        };
    }

    /**
     * Get FCM body enrichi
     */


    private function getFcmBody(): string
    {
        $data = $this->getData()();

        if ($data['recipient'] === 'salon') {
            return "{$data['client_name']} • {$data['services_count']} service(s) • {$data['booking_time']} • {$data['total_price']} Fcfa • dans {$data['time_until_hours']}h";
        }

        return "{$data['salon_name']} • {$data['services_count']} service(s) • {$data['booking_time']} • {$data['total_price']} Fcfa • dans {$data['time_until_hours']}h";
    }

    /**
     * Ajouter des détails pour les clients dans l'email
     */
    private function addClientEmailDetails(MailMessage $mailMessage): MailMessage
    {
        $services = $this->getServiceNames();
        $timeInfo = $this->getTimeUntilAppointment();
        $location = $this->booking->at_salon ? 
            "au salon {$this->booking->salon->name}" : 
            "à votre domicile";
        
        return $mailMessage
            ->line("💇 **Services réservés :** {$services}")
            ->line("📍 **Lieu :** {$location}")
            ->line("⏱️ **Temps restant :** {$timeInfo['message']}")
            ->line("💰 **Prix total :** " . number_format($this->booking->getTotal(), 2) . " €")
            ->when($this->booking->hint, function($mail) {
                return $mail->line("📝 **Note spéciale :** {$this->booking->hint}");
            })
            ->when($this->booking->salon->phone_number, function($mail) {
                return $mail->line("📞 **Téléphone du salon :** {$this->booking->salon->phone_number}");
            })
            ->when($this->booking->employee, function($mail) {
                return $mail->line("👨‍💼 **Coiffeur assigné :** {$this->booking->employee->name}");
            });
    }

    /**
     * Ajouter des détails pour le salon dans l'email
     */
    private function addSalonEmailDetails(MailMessage $mailMessage): MailMessage
    {
        $services = $this->getServiceNames();
        $timeInfo = $this->getTimeUntilAppointment();
        $clientPhone = $this->booking->user->phone_number ?? 'Non renseigné';
        
        return $mailMessage
            ->line("👤 **Client :** {$this->booking->user->name}")
            ->line("📞 **Téléphone client :** {$clientPhone}")
            ->line("💇 **Services demandés :** {$services}")
            ->line("⏱️ **Temps restant :** {$timeInfo['message']}")
            ->line("💰 **Montant :** " . number_format($this->booking->getTotal(), 2) . " €")
            ->line("📍 **Lieu :** " . ($this->booking->at_salon ? 'Au salon' : 'À domicile'))
            ->when($this->booking->hint, function($mail) {
                return $mail->line("📝 **Demande spéciale :** {$this->booking->hint}");
            })
            ->when($this->booking->employee, function($mail) {
                return $mail->line("👨‍💼 **Employé assigné :** {$this->booking->employee->name}");
            });
    }

    /**
     * Obtenir les détails complets des services
     */
    private function getDetailedServices(): array
    {
        if (empty($this->booking->e_services)) {
            return [];
        }
        
        $services = [];
        foreach ($this->booking->e_services as $service) {
            $services[] = [
                'id' => $service->id ?? null,
                'name' => $service->name ?? 'Service inconnu',
                'price' => isset($service->price) ? number_format($service->getPrice(), 2) . ' €' : null,
                'duration' => $service->duration ?? null,
                'description' => $service->description ?? null,
                'category' => $service->category->name ?? null,
            ];
        }
        
        return $services;
    }

    /**
     * Obtenir les détails des options sélectionnées
     */
     private function getDetailedOptions(): array
    {
        if (empty($this->booking->options)) {
            return [];
        }
        
        $options = [];
        foreach ($this->booking->options as $option) {
            $options[] = [
                'id' => $option->id ?? null,
                'name' => $option->name ?? 'Option inconnue',
                'price' => isset($option->price) ? number_format($option->price, 2) . ' €' : null,
                'description' => $option->description ?? null,
            ];
        }
        
        return $options;
    }

    /**
     * Calculer le temps restant jusqu'au rendez-vous
     */
    private function getTimeUntilAppointment(): array
    {
        $now = Carbon::now();
        $bookingTime = Carbon::parse($this->booking->booking_at);
        
        if ($now->isAfter($bookingTime)) {
            return [
                'status' => 'passed',
                'message' => 'Rendez-vous déjà passé',
                'total_hours' => 0
            ];
        }
        
        $diff = $now->diff($bookingTime);
        
        return [
            'status' => 'upcoming',
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'total_hours' => round($now->diffInHours($bookingTime, false), 1),
            'human_readable' => $now->diffForHumans($bookingTime, true),
            'message' => $this->getTimeMessage($diff)
        ];
    }

    /**
     * Générer un message temporel contextuel
     */
    private function getTimeMessage($diff): string
    {
        if ($diff->days > 0) {
            return "Dans {$diff->days} jour" . ($diff->days > 1 ? 's' : '') . " et {$diff->h}h{$diff->i}";
        } elseif ($diff->h > 0) {
            return "Dans {$diff->h}h{$diff->i}";
        } else {
            return "Dans {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
        }
    }

    /**
     * Get service names as string
     */
    private function getServiceNames(): string
    {
        if (empty($this->booking->e_services)) {
            return 'Service';
        }
        
        $names = collect($this->booking->e_services)->pluck('name')->toArray();
        return implode(', ', $names);
    }

    /**
     * Formater l'adresse du salon
     */
    private function getFormattedAddress(): ?string
    {
        if (!$this->booking->salon || !$this->booking->salon->address) {
            return null;
        }
        
        $address = $this->booking->salon->address;
        $parts = array_filter([
            $address->address ?? null,
            $address->city ?? null,
            $address->postal_code ?? null,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Formater l'adresse du client (pour services à domicile)
     */
    private function getFormattedCustomerAddress(): ?string
    {
        if (!$this->booking->address) {
            return null;
        }
        
        $address = $this->booking->address;
        $parts = array_filter([
            $address->address ?? null,
            $address->city ?? null,
            $address->postal_code ?? null,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Obtenir les détails de localisation
     */
    private function getLocationDetails(): array
    {
        if ($this->booking->at_salon) {
            return [
                'type' => 'salon',
                'name' => $this->booking->salon->name ?? '',
                'address' => $this->getFormattedAddress(),
                'phone' => $this->booking->salon->phone_number ?? null,
            ];
        }
        
        return [
            'type' => 'domicile',
            'address' => $this->getFormattedCustomerAddress(),
        ];
    }

    /**
     * Obtenir les détails de prix
     */
    private function getPriceDetails(): array
    {
        return [
            'subtotal' => $this->booking->getSubtotal(),
            'taxes' => $this->booking->getTaxesValue(),
            'discount' => $this->booking->coupon ? $this->booking->getCouponValue() : 0,
            'total' => $this->booking->getTotal(),
            'currency' => 'EUR',
        ];
    }

    /**
     * Générer des liens profonds pour l'app mobile
     */
    private function getDeepLink(string $type, ?int $id): ?string
    {
        if (!$id) return null;
        
        $baseUrl = config('app.mobile_deep_link_base', 'bhc://');
        
        return match($type) {
            'booking' => $baseUrl . "booking/{$id}",
            'salon' => $baseUrl . "salon/{$id}",
            default => null
        };
    }

    /**
     * Générer un lien vers les directions GPS
     */
    private function getDirectionsLink(): ?string
    {
        $address = $this->booking->at_salon ? 
            $this->booking->salon->address : 
            $this->booking->address;
            
        if (!$address || !isset($address->latitude, $address->longitude)) {
            return null;
        }
        
        return "https://www.google.com/maps/dir/?api=1&destination={$address->latitude},{$address->longitude}";
    }

    /**
     * Get salon media URL
     */
    private function getSalonMediaUrl(): string
    {
        if ($this->booking->salon && $this->booking->salon->hasMedia('image')) {
            return $this->booking->salon->getFirstMediaUrl('image', 'thumb');
        }
        return asset('images/image_default.png');
    }
}