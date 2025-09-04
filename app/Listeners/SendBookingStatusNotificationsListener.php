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

            Log::error(['handle',$event->booking->user]);

            if ($event->booking->at_salon) {
                if ($event->booking->bookingStatus->order < 20) {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                } else if ($event->booking->bookingStatus->order >= 20 && $event->booking->bookingStatus->order < 40) {
                    Notification::send($event->booking->salon->users, new StatusChangedBooking($event->booking));
                } else {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                }
            } else {
                if ($event->booking->bookingStatus->order < 40) {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                } else {
                    Notification::send($event->booking->salon->users, new StatusChangedBooking($event->booking));
                }
            }

            // Statut 1 = "Received" (nouvelle réservation)
            if ($event->booking->bookingStatus->order === 1) {
                Log::info("Nouvelle réservation détectée (statut Received) - Planification des rappels pour la réservation {$event->booking->id}");
                $this->reminderService->scheduleAllReminders($event->booking);
            }

            // NOUVELLE LOGIQUE : Replanifier les rappels si la date/heure de la réservation change
            if (isset($event->booking->getOriginal()['booking_at']) && 
                $event->booking->getOriginal()['booking_at'] !== $event->booking->booking_at->format('Y-m-d H:i:s')) {
                Log::info("Changement d'heure détecté pour la réservation {$event->booking->id} - Replanification des rappels");
                $this->reminderService->rescheduleReminders($event->booking);
            }


        } catch (Exception $e) {
            Log::error("Erreur dans SendBookingStatusNotificationsListener: " . $e->getMessage());
        }
    }
}
