<?php

namespace App\Listeners;

use App\Events\WalletTransactionCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreatedWalletTransactionListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WalletTransactionCreatedEvent $event): void
    {
        try {
            $transaction = $event->walletTransaction;
            
            if ($transaction->action == 'credit') {
                $transaction->wallet->balance += $transaction->amount;
            } else if ($transaction->action == 'debit') {
                $transaction->wallet->balance -= $transaction->amount;
            }
            $transaction->wallet->save();
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('listeners_transactions')->error('Erreur lors de la mise à jour du solde du portefeuille', [
                'exception' => $e,
                'transaction' => $event->walletTransaction,
            ]);
        }
    }
}
