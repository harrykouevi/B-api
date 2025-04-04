<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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


}
