<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VegetaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:vegeta-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commande qui écrit dans vegeta.log toutes les 2 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         // Écrire un message dans le log vegeta.log
        Log::channel('vegeta')->info('Commande vegeta exécutée à ' . now());

        $this->info('Log écrit avec succès.' . now());
        return 0;
    }
}
