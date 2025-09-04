<?php

namespace App\Console\Commands;

use App\Services\BookingReminderService;
use Illuminate\Console\Command;

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
        $this->info('🔄 Début du traitement des rappels manqués...');

        try {
            $processedCount = $this->reminderService->processMissedReminders();
            
            $this->info("✅ Traitement terminé avec succès. {$processedCount} réservations traitées.");
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du traitement: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}