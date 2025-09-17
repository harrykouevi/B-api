<?php

namespace App\Console\Commands;

use App\Services\BookingReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMissedRemindersCommand extends Command
{
    protected $signature = 'bookings:process-missed-reminders';
    protected $description = 'Traite les rappels de réservation qui ont été manqués ou doivent être replanifiés';

    private BookingReminderService $reminderService;

    public function __construct(BookingReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle(): int
    {
        $this->info('🔄 Début du traitement des rappels manqués...'. now());
        Log::channel('vegeta')->info('🔄 Début du traitement des rappels manqués... ' . now());

        try {
            $processedCount = $this->reminderService->processMissedReminders();
            
            Log::info("✅ Traitement terminé avec succès. {$processedCount} réservations traitées.");
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            // $this->error("❌ Erreur lors du traitement: " . $e->getMessage());
            Log::error('Erreur lors du traitement des rappels manqués', [
                'exception' => $e,
            ]);
            return self::FAILURE;
        }
    }
}