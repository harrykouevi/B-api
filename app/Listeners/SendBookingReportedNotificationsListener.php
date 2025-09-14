<?php

namespace App\Listeners;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Events\BookingReportedEvent;
use App\Notifications\BookingReportedClientNotification;
use App\Notifications\BookingReportedSalonNotification;

class SendBookingReportedNotificationsListener
{
    /**
     * Handle the event.
     *
     * @param BookingReportedEvent $event
     * @return void
     */
    public function handle(BookingReportedEvent $event): void
    {
        try {
            $originalBooking = $event->originalBooking;
            $newBooking = $event->newBooking;

            Log::info("Booking reported event received", [
                'original_booking_id' => $originalBooking->id,
                'new_booking_id' => $newBooking->id,
            ]);

            // Send notification to client
            $this->notifyClient($originalBooking, $newBooking);

            // Send notification to salon owners and employees
            $this->notifySalon($originalBooking, $newBooking);

        } catch (Exception $e) {
            Log::error("Error in SendBookingReportedNotificationsListener: " . $e->getMessage());
        }
    }

    /**
     * Send notification to client
     *
     * @param $originalBooking
     * @param $newBooking
     * @return void
     */
    private function notifyClient($originalBooking, $newBooking): void
    {
        try {
            Log::info("Sending booking reported notification to client", [
                'booking_id' => $originalBooking->id,
                'client_id' => $originalBooking->user_id,
            ]);

            $client = $originalBooking->user;
            if ($client) {
                Notification::send(
                    [$client],
                    new BookingReportedClientNotification($originalBooking, $newBooking)
                );
            }
        } catch (Exception $e) {
            Log::error("Error sending booking reported notification to client: " . $e->getMessage());
        }
    }

    /**
     * Send notification to salon owners and employees
     *
     * @param $originalBooking
     * @param $newBooking
     * @return void
     */
    private function notifySalon($originalBooking, $newBooking): void
    {
        try {
            Log::info("Sending booking reported notification to salon", [
                'booking_id' => $originalBooking->id,
                'salon_id' => $originalBooking->salon_id,
            ]);

            if (!$originalBooking->salon) {
                return;
            }

            $salonUsers = $originalBooking->salon->users ?? $originalBooking->salon->users()->get();
            
            // Filter users with roles (salon owner or employees)
            $recipients = $salonUsers->filter(function ($user) {
                return $user->roles->count() > 0;
            });

            if ($recipients->count() > 0) {
                Notification::send(
                    $recipients,
                    new BookingReportedSalonNotification($originalBooking, $newBooking)
                );
            }
        } catch (Exception $e) {
            Log::error("Error sending booking reported notification to salon: " . $e->getMessage());
        }
    }
}