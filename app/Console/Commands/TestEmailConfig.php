<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class TestEmailConfig extends Command
{
    protected $signature = 'test:email-config';
    protected $description = 'Teste la configuration email pour les rappels';

    public function handle()
    {
        // Afficher la configuration actuelle
        $this->info('Configuration Email Actuelle :');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('MAIL_FROM: darkponey310@gmail.com');
        
        // Tester l'envoi
        try {
            Mail::raw('Test de configuration email pour BHC - ' . now(), function($message) {
                $message->to('darkponey310@gmail.com')
                       ->subject('🧪 Test Configuration Email BHC - ' . now()->format('H:i:s'));
            });
            
            $this->info('✅ Email envoyé avec succès !');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }
}