<?php
/*
 * File name: SendBookingStatusNotificationsListener.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Listeners;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\BookingReminderService;
use App\Notifications\StatusChangedBooking;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OwnerStatusChangedBooking;
use Carbon\Carbon;

/**
 * Class SendBookingStatusNotificationsListener
 * @package App\Listeners
 */
class SendBookingStatusNotificationsListener
{

    private BookingReminderService $reminderService;

    public function __construct(BookingReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Handle the event.
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        try{

            $booking = $event->booking;
            Log::info([
                'Booking event reçu',
                'id'     => $booking->id,
                'status' => $booking->bookingStatus->order,
                'at_salon' => $booking->at_salon,
                'salon' => $booking->salon->toArray(),
            ]);

            
            /**
             * ───────────────────────────────────────────────
             * SECTION 1 : Notifications (tout est géré ici)
             * ───────────────────────────────────────────────
             */
            $this->handleStatusNotifications($booking);


            /**
             * ───────────────────────────────────────────────
             * SECTION 2 : Planification & replanification des rappels
             * ───────────────────────────────────────────────
             */
            // Planifier les rappels uniquement si booking payé et accepté
            // Statut 10 = "Accepted" 
            if ($booking->bookingStatus->order === 10) {
                $this->reminderService->scheduleAllReminders($booking);
            }

            // Replanification si changement de date/heure
            if (isset($booking->getOriginal()['booking_at']) &&
                !Carbon::parse($booking->getOriginal()['booking_at'])->equalTo($booking->booking_at)) {
                Log::info("Changement d'heure pour booking #{$booking->id} → Replanification des rappels");
                $this->reminderService->rescheduleReminders($booking);
            }


        } catch (Exception $e) {
            Log::error("Erreur dans SendBookingStatusNotificationsListener: " . $e->getMessage());
        }
    }


    /**
     * Gère les notifications selon le statut et at_salon
     */
    private function handleStatusNotifications($booking): void
    {
        // Ne pas envoyer les notifications génériques pour les reports
        // Ces notifications sont gérées par SendBookingReportedNotificationsListener
        if ($booking->bookingStatus->order == 80) {
            return;
        }
        
        if (in_array($booking->bookingStatus->order, [1])) {
            // Recu → notifier le client et le coiffeur
            Log::info("viens peut etre de creer ou reporter booking #{$booking->id} → ");

            $this->notifyClient($booking);
            $this->notifySalonOwners($booking);

        } else{
            if ($booking->at_salon) {
                Log::info("Notification pour booking au salon  : r#{$booking->id}");

                if ($booking->bookingStatus->order < 20) {
                    // Accepté → notifier le client
                    $this->notifyClient($booking);

                } elseif ($booking->bookingStatus->order < 40) {
                    // En chemin, arrivé → notifier le salon
                    $this->notifySalonOwners($booking);

                } else {
                    // Après l’arrivée (service en cours, terminé, annulé, etc.) → notifier le client
                    $this->notifyClient($booking);
                }
            } else {
                Log::info("Notification pour booking à domicile  : r#{$booking->id}");

                if ($booking->bookingStatus->order < 40) {
                    // Avant l’arrivée → notifier le client
                    $this->notifyClient($booking);
                } else {
                    // Après l’arrivée → notifier le salon
                    $this->notifySalonOwners($booking);
                }
            }
        }
        
    }

    /**
     * Notification client
     */
    private function notifyClient($booking): void
    {   try{
            Log::info("Notification pour booking #{$booking->id} → Notification envoyé au clien");
            Notification::send([$booking->user], new StatusChangedBooking($booking));
        } catch (Exception $e) {
            Log::error("Erreur dans SendBookingStatusNotificationsListener avec l'envoie de notifications: " . $e->getMessage());
        }
    }

    /**
     * Notification salon (owners + employés)
     */
    private function notifySalonOwners($booking): void
    {
        Log::info("Notification pour booking #{$booking->id} → Notification envoyé au salon");

        if (!$booking->salon) {
            return;
        }

        $salonUsers = $booking->salon->users ?? $booking->salon->users()->get();
        $recipients = $salonUsers->filter(fn($user) =>
            $user->hasRole('salon owner')
        );

        Log::info("Recipients for booking #{$booking->id}", [
            'ids' => $recipients->pluck('id')->toArray(),
        ]);

        try{
            if ($recipients->count() > 0) {
                Notification::send($recipients, new OwnerStatusChangedBooking($booking));
            }
        } catch (Exception $e) {
            Log::error("Erreur dans SendBookingStatusNotificationsListener avec l'envoie de notifications: " . $e->getMessage());
        }
    }
}