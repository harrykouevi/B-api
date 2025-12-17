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
            Log::info('=== SendBookingStatusNotificationsListener START ===', [
                'booking_id' => $booking->id,
                'booking_status_id' => $booking->booking_status_id,
                'booking_status_order' => $booking->bookingStatus->order,
                'booking_status_name' => $booking->bookingStatus->status ?? 'N/A',
                'at_salon' => $booking->at_salon,
                'salon_id' => $booking->salon->id ?? 'N/A',
                'user_id' => $booking->user_id,
            ]);


            /**
             * ───────────────────────────────────────────────
             * SECTION 1 : Notifications (tout est géré ici)
             * ───────────────────────────────────────────────
             */
            Log::info('SendBookingStatusNotificationsListener - Appel handleStatusNotifications', [
                'booking_id' => $booking->id,
                'status_order' => $booking->bookingStatus->order
            ]);

            $this->handleStatusNotifications($booking);

            Log::info('SendBookingStatusNotificationsListener - handleStatusNotifications terminé', [
                'booking_id' => $booking->id
            ]);


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

            Log::info('=== SendBookingStatusNotificationsListener END ===', [
                'booking_id' => $booking->id,
                'status_order' => $booking->bookingStatus->order
            ]);

        } catch (Exception $e) {
            Log::error("SendBookingStatusNotificationsListener - EXCEPTION GENERALE", [
                'booking_id' => $event->booking->id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }


    /**
     * Gère les notifications selon le statut et at_salon
     */
    private function handleStatusNotifications($booking): void
    {
        Log::info('SendBookingStatusNotificationsListener - handleStatusNotifications DEBUT', [
            'booking_id' => $booking->id,
            'status_order' => $booking->bookingStatus->order,
            'at_salon' => $booking->at_salon
        ]);

        // Ne pas envoyer les notifications génériques pour les reports
        // Ces notifications sont gérées par SendBookingReportedNotificationsListener
        if ($booking->bookingStatus->order == 80) {
            Log::info('SendBookingStatusNotificationsListener - Status 80 (Report), skip notifications');
            return;
        }

        if (in_array($booking->bookingStatus->order, [1])) {
            // Recu → notifier le client et le coiffeur
            Log::info("SendBookingStatusNotificationsListener - Status 1 (Received), notifier client ET salon", [
                'booking_id' => $booking->id
            ]);

            $this->notifyClient($booking);
            $this->notifySalonOwners($booking);

        } else{
            if ($booking->at_salon) {
                Log::info("SendBookingStatusNotificationsListener - Booking AU SALON", [
                    'booking_id' => $booking->id,
                    'status_order' => $booking->bookingStatus->order
                ]);

                if ($booking->bookingStatus->order < 20) {
                    // Accepté (order=10) → notifier le client ET le salon
                    Log::info("SendBookingStatusNotificationsListener - Status < 20 (Accepté), notifier client ET salon", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);

                    $this->notifyClient($booking);
                    $this->notifySalonOwners($booking);

                } elseif ($booking->bookingStatus->order < 40) {
                    // En chemin, arrivé → notifier le salon
                    Log::info("SendBookingStatusNotificationsListener - Status 20-39 (En chemin/Arrivé au salon), notifier salon", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifySalonOwners($booking);

                }elseif ($booking->bookingStatus->order < 60) {
                    // Accepté → notifier le client
                    Log::info("SendBookingStatusNotificationsListener - Status 40-59 (Service), notifier client", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifyClient($booking);

                }elseif (in_array($booking->bookingStatus->order, [60,70])) {
                    // En chemin, arrivé → notifier le salon
                    Log::info("SendBookingStatusNotificationsListener - Status 60/70 (Terminé/Payé), notifier salon ET client", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifySalonOwners($booking);
                    $this->notifyClient($booking);


                } else {
                    // Après l'arrivée (service en cours, terminé, annulé, etc.) → notifier le client
                    Log::info("SendBookingStatusNotificationsListener - Status > 70 (Annulé/Autre), notifier client", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifyClient($booking);
                }
            } else {
                Log::info("SendBookingStatusNotificationsListener - Booking A DOMICILE", [
                    'booking_id' => $booking->id,
                    'status_order' => $booking->bookingStatus->order
                ]);

                if ($booking->bookingStatus->order < 20) {
                    // Accepté (order=10) → notifier le client ET le salon
                    Log::info("SendBookingStatusNotificationsListener - Status < 20 (Accepté à domicile), notifier client ET salon", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifyClient($booking);
                    $this->notifySalonOwners($booking);
                } elseif ($booking->bookingStatus->order < 40) {
                    // Avant l'arrivée (order 20-30) → notifier le client
                    Log::info("SendBookingStatusNotificationsListener - Status 20-39 (Avant arrivée domicile), notifier client", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifyClient($booking);
                }elseif (in_array($booking->bookingStatus->order, [60,70])) {
                    // En chemin, arrivé → notifier le salon
                    Log::info("SendBookingStatusNotificationsListener - Status 60/70 (Terminé domicile), notifier salon ET client", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifySalonOwners($booking);
                    $this->notifyClient($booking);

                }  else {
                    // Après l'arrivée → notifier le salon
                    Log::info("SendBookingStatusNotificationsListener - Status > 40 (Après arrivée domicile), notifier salon", [
                        'booking_id' => $booking->id,
                        'status_order' => $booking->bookingStatus->order
                    ]);
                    $this->notifySalonOwners($booking);
                }
            }
        }

        Log::info('SendBookingStatusNotificationsListener - handleStatusNotifications FIN', [
            'booking_id' => $booking->id
        ]);

    }

    /**
     * Notification client
     */
    private function notifyClient($booking): void
    {
        try{
            Log::info("SendBookingStatusNotificationsListener - notifyClient DEBUT", [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_email' => $booking->user->email ?? 'N/A',
                'user_name' => $booking->user->name ?? 'N/A',
                'status_order' => $booking->bookingStatus->order,
                'status_name' => $booking->bookingStatus->status ?? 'N/A'
            ]);

            Notification::send([$booking->user], new StatusChangedBooking($booking));

            Log::info("SendBookingStatusNotificationsListener - notifyClient SUCCESS", [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'notification_type' => 'StatusChangedBooking'
            ]);
        } catch (Exception $e) {
            Log::error("SendBookingStatusNotificationsListener - notifyClient ERROR", [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notification salon (owners + employés)
     */
    private function notifySalonOwners($booking): void
    {
        Log::info("SendBookingStatusNotificationsListener - notifySalonOwners DEBUT", [
            'booking_id' => $booking->id,
            'salon_id' => $booking->salon->id ?? 'N/A',
            'salon_name' => $booking->salon->name ?? 'N/A',
            'status_order' => $booking->bookingStatus->order,
            'status_name' => $booking->bookingStatus->status ?? 'N/A'
        ]);

        if (!$booking->salon) {
            Log::warning("SendBookingStatusNotificationsListener - notifySalonOwners SKIP: Pas de salon", [
                'booking_id' => $booking->id
            ]);
            return;
        }

        $salonUsers = $booking->salon->users ?? collect() ;
        // $salonUsers = $salonUsers->filter(fn($user) =>
        //     $user->hasRole('salon owner')
        // );

        Log::info("SendBookingStatusNotificationsListener - notifySalonOwners Recipients", [
            'booking_id' => $booking->id,
            'salon_id' => $booking->salon->id,
            'users_count' => $salonUsers->count(),
            'user_ids' => $salonUsers->pluck('id')->toArray(),
            'user_emails' => $salonUsers->pluck('email')->toArray()
        ]);

        if ($salonUsers->count() === 0) {
            Log::warning("SendBookingStatusNotificationsListener - notifySalonOwners SKIP: Aucun utilisateur dans le salon", [
                'booking_id' => $booking->id,
                'salon_id' => $booking->salon->id
            ]);
            return;
        }

        try{
            Notification::send($salonUsers, new OwnerStatusChangedBooking($booking));

            Log::info("SendBookingStatusNotificationsListener - notifySalonOwners SUCCESS", [
                'booking_id' => $booking->id,
                'salon_id' => $booking->salon->id,
                'users_notified' => $salonUsers->count(),
                'notification_type' => 'OwnerStatusChangedBooking'
            ]);
        } catch (Exception $e) {
            Log::error("SendBookingStatusNotificationsListener - notifySalonOwners ERROR", [
                'booking_id' => $booking->id,
                'salon_id' => $booking->salon->id ?? 'N/A',
                'users_count' => $salonUsers->count(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}