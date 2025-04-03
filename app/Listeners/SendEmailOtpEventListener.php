<?php

namespace App\Listeners;

use App\Events\SendEmailOtpEvent;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailOtpEventListener
{

     /**
     * @var NotificationService
     */
    private NotificationService $notificationService;

     /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService =  $notificationService ;
    }

    /**
     * Handle the event.
     */
    public function handle(SendEmailOtpEvent $event): void
    {
        try {
            $event->user->otp = rand(10000, 99999); // Generate 5-digit OTP
            $event->user->otp_expires_at = Carbon::now()->addMinutes(10); // Set expiration time
            $event->user->save();

            // Envoi à l'utilisateur
            $this->notificationService->sendOTPCodeMessage( $event->user);
            
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('listeners_transactions')->error('Erreur lors de l\' envoi du code OTP à l\'utilisateur #' . $event->user->id, [
                'exception' => $e,
            ]);
        }
    }
}
