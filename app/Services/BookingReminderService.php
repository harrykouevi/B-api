<?php

namespace App\Services;

use App\Jobs\BookingReminderJob;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingReminderService
{
    /**
     * Planifier tous les rappels pour une réservation
     *
     * @param Booking $booking
     * @return void
     */
    public function scheduleAllReminders(Booking $booking): void
    {
        if (!$booking || $booking->cancel || !$booking->booking_at) {
            Log::info("Rappels non planifiés - Réservation invalide ou annulée: {$booking->id}");
            return;
        }

        $bookingTime = Carbon::parse($booking->booking_at);
        $now = Carbon::now();

        // Vérifier si la réservation n'est pas déjà passée
        if ($now->isAfter($bookingTime)) {
            Log::info("Rappels non planifiés - Réservation {$booking->id} déjà passée ({$bookingTime->format('Y-m-d H:i')})");
            return;
        }

        Log::info("Planification des rappels pour la réservation {$booking->id} prévue le {$bookingTime->format('Y-m-d H:i')}");

        // 1. Confirmation immédiate
        $this->scheduleConfirmationReminder($booking);

        // 2. Rappel à J-1 (24h avant)
        $this->schedule24HourReminder($booking, $bookingTime, $now);

        // 3. Rappel à H-3 (3h avant)
        $this->schedule3HourReminder($booking, $bookingTime, $now);

        // 4. Rappel à H-30 ou H-15 (selon si réservation tardive)
        $this->scheduleFinalReminder($booking, $bookingTime, $now);
    }

    /**
     * Confirmation immédiate après réservation
     */
    private function scheduleConfirmationReminder(Booking $booking): void
    {
        BookingReminderJob::dispatch($booking, 'confirmation')
            ->onQueue('notifications')
            ->delay(now()->addMinutes(1));
        
        Log::info("Confirmation planifiée pour la réservation {$booking->id} dans 1 minute");
    }

    /**
     * Rappel à J-1 avec logique pour éviter 22h-7h
     */
    private function schedule24HourReminder(Booking $booking, Carbon $bookingTime, Carbon $now): void
    {
        $reminderTime = $bookingTime->copy()->subHours(24);
        $originalReminderTime = $reminderTime->copy();

        // Si le rappel tombe entre 22h et 7h, le reporter à 7h
        if ($reminderTime->hour >= 22 || $reminderTime->hour < 7) {
            if ($reminderTime->hour >= 22) {
                // 22h-23h59 → 7h du lendemain
                $reminderTime->addDay()->setTime(7, 0, 0);
            } else {
                // 0h-6h59 → 7h du même jour
                $reminderTime->setTime(7, 0, 0);
            }
            
            Log::info("Rappel 24h reporté de {$originalReminderTime->format('H:i')} à {$reminderTime->format('Y-m-d H:i')} pour éviter les heures nocturnes");
        }

        // Planifier seulement si le rappel est dans le futur
        if ($now->isBefore($reminderTime)) {
            BookingReminderJob::dispatch($booking, '24h')
                ->onQueue('notifications')
                ->delay($reminderTime);
            
            Log::info("Rappel 24h planifié pour la réservation {$booking->id} le {$reminderTime->format('Y-m-d H:i:s')}");
        } else {
            Log::info("Rappel 24h non planifié - Heure déjà passée: {$reminderTime->format('Y-m-d H:i')}");
        }
    }

    /**
     * Rappel à H-3 (3h avant)
     */
    private function schedule3HourReminder(Booking $booking, Carbon $bookingTime, Carbon $now): void
    {
        $reminderTime = $bookingTime->copy()->subHours(3);

        if ($now->isBefore($reminderTime)) {
            BookingReminderJob::dispatch($booking, '3h')
                ->onQueue('notifications')
                ->delay($reminderTime);
            
            Log::info("Rappel 3h planifié pour la réservation {$booking->id} le {$reminderTime->format('Y-m-d H:i:s')}");
        } else {
            Log::info("Rappel 3h non planifié - Heure déjà passée: {$reminderTime->format('Y-m-d H:i')}");
        }
    }

    /**
     * Rappel final : H-30 ou H-15 si réservation tardive
     */
    private function scheduleFinalReminder(Booking $booking, Carbon $bookingTime, Carbon $now): void
    {
        $hoursUntilBooking = $now->diffInHours($bookingTime, false);
        $isLateBooking = $hoursUntilBooking <= 2;
        
        $minutes = $isLateBooking ? 15 : 30;
        $reminderTime = $bookingTime->copy()->subMinutes($minutes);
        $reminderType = $isLateBooking ? '15min' : '30min';

        if ($now->isBefore($reminderTime)) {
            BookingReminderJob::dispatch($booking, $reminderType)
                ->onQueue('notifications')
                ->delay($reminderTime);
            
            $reasonMsg = $isLateBooking ? " (réservation tardive - {$hoursUntilBooking}h restantes)" : "";
            Log::info("Rappel {$reminderType} planifié pour la réservation {$booking->id} le {$reminderTime->format('Y-m-d H:i:s')}{$reasonMsg}");
        } else {
            Log::info("Rappel {$reminderType} non planifié - Heure déjà passée: {$reminderTime->format('Y-m-d H:i')}");
        }
    }

    /**
     * Annuler tous les rappels pour une réservation (logiquement)
     */
    public function cancelAllReminders(Booking $booking): void
    {
        // Note: Laravel ne permet pas d'annuler facilement des jobs planifiés
        // Nous utilisons la vérification dans le Job lui-même pour gérer les annulations
        Log::info("Demande d'annulation logique des rappels pour la réservation {$booking->id}");
    }

    /**
     * Replanifier les rappels pour une réservation modifiée
     */
    public function rescheduleReminders(Booking $booking): void
    {
        Log::info("Replanification des rappels pour la réservation {$booking->id}");
        $this->cancelAllReminders($booking);
        $this->scheduleAllReminders($booking);
    }

    /**
     * Vérifier et planifier les rappels manqués
     */
    public function processMissedReminders(): int
    {
        $processedCount = 0;
        
        // Récupérer les réservations futures sans annulation
        $upcomingBookings = Booking::with(['user', 'salon', 'bookingStatus'])
            ->where('booking_at', '>', Carbon::now())
            ->where('cancel', false)
            ->whereHas('bookingStatus', function ($query) {
                $query->where('order', '<', 60); // Statuts avant "Done"
            })
            ->get();

        /** @var \App\Models\Booking $booking */
        foreach ($upcomingBookings as $booking) {
            try {
                $this->scheduleAllReminders($booking);
                $processedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors de la replanification pour la réservation {$booking->id}: " . $e->getMessage());
            }
        }

        Log::info("Traitement des rappels manqués terminé: {$processedCount} réservations traitées");
        return $processedCount;
    }
}