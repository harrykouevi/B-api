<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class BookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $booking;
    public $reminderType;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Booking $booking
     * @param string $reminderType (confirmation, 24h, 3h, 30min, 15min)
     */
    public function __construct(Booking $booking, string $reminderType)
    {
        $this->booking = $booking;
        $this->reminderType = $reminderType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Vérifier si la réservation existe toujours et n'est pas annulée
            $currentBooking = Booking::with([
                'user', 
                'bookingStatus', 'payment', 'employee'
            ])->find($this->booking->id);
            
            if (!$currentBooking || $currentBooking->cancel || 
                in_array($currentBooking->bookingStatus->order, [60, 70, 80])) {
                Log::info("Rappel {$this->reminderType} annulé - Réservation {$this->booking->id} supprimée ou annulée");
                return;
            }

            // Vérifier si la réservation n'est pas déjà passée
            if (Carbon::now()->isAfter($currentBooking->booking_at)) {
                Log::info("Rappel {$this->reminderType} annulé - Réservation {$this->booking->id} déjà passée");
                return;
            }

            // Envoyer le rappel au client
            if ($currentBooking->user) {
                try{
                    Notification::send(
                        [$currentBooking->user], 
                        new BookingReminderNotification($currentBooking, $this->reminderType, 'client')
                    );
                } catch (\Exception $e) {
                    Log::error("Erreur envoi Notification au client {$this->reminderType} pour la réservation {$this->booking->id}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                Log::info("Rappel {$this->reminderType} envoyé au client {$currentBooking->user->name} pour la réservation {$this->booking->id}");
            }

            // Envoyer le rappel au salon (sauf pour confirmation)
            if ($this->reminderType !== 'confirmation' && $currentBooking->salon && $currentBooking->salon->users->isNotEmpty()) {
                try{
                    Notification::send(
                        $currentBooking->salon->users, 
                        new BookingReminderNotification($currentBooking, $this->reminderType, 'salon')
                    );
                } catch (\Exception $e) {
                    Log::error("Erreur envoi Notification au salon {$this->reminderType} pour la réservation {$this->booking->id}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                Log::info("Rappel {$this->reminderType} envoyé au salon {$currentBooking->salon->name} pour la réservation {$this->booking->id}");
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du rappel {$this->reminderType} pour la réservation {$this->booking->id}: " . $e->getMessage(), [
                 'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Échec définitif du rappel {$this->reminderType} pour la réservation {$this->booking->id}: " . $exception->getMessage(), [
                 'trace' => $exception->getTraceAsString()
            ]);
    }
}