<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Exception;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;


class NotificationService extends Mailable
{
    use Queueable, SerializesModels;
    // Define the ReminderDetails class inside the ReminderComponent


    // Method that accepts the ReminderDetails object
    public function sendOTPCodeMessage(User $user)
    {
        $emailContent = "
            <h1>Code de Vérification (OTP)</h1>
            <p>Bonjour $user->name,</p>
            <p>Votre code de vérification est : <strong>$user->otp</strong></p>
            <p>Ce code expirera dans 10 minutes.</p>
            <p>Merci !</p>
            <p>Cordialement,<br>L'équipe de  ". setting('app_name', '')."</p>
        ";
        // email admin
        Mail::html($emailContent, function ($mail) use ($user) {
            $mail->from(config('mail.from.address'), config('mail.from.name'))
                 ->to($user->email)  // Envoi à l'administrateur
                 ->subject('Votre Code de Vérification (OTP)');
        });

    }



    /**
    * @param mixed  $notification
    *
    */
    public static function notify(\Illuminate\Support\Collection|Array $notifiables, $notification)
    {

        foreach ($notifiables as $user) {
            try {
                Notification::send($user, $notification);
            } catch (Exception $e) {
                
                // Détection spécifique d'un token FCM invalide
                $isFcmTokenError = str_contains($e->getMessage(), '404 Not Found') ||
                                str_contains($e->getMessage(), 'Requested entity was not found');

                if ($isFcmTokenError) {
                    // Token FCM invalide - tenter de supprimer les tokens invalides
                    Log::error('Erreur notification utilisateur', [
                        'user_id' => $user->id,
                        'notification' => get_class($notification),
                        'message' => $e->getMessage(),
                    ]);

                    
                    if ($user->device_token) {
                        $user->update(['device_token' => null]);
                        Log::info("Token FCM supprimé pour salon owner", [
                            'user_id' => $user->id,
                            'user_email' => $user->email
                        ]);
                    }
                    

                }else{
                    throw $e; 
                }
            }
        }
    }


}
