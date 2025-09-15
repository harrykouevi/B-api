<?php

namespace App\Listeners;

use App\Criteria\Purchases\PurchasesOfUserCriteria;
use App\Criteria\Wallets\WalletTransactionsOfUserCriteria;
use App\Events\NotifyPaymentEvent;
use App\Models\Wallet;
use App\Notifications\NewDebitPayment;
use App\Notifications\NewReceivedPayment;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Types\PaymentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;


class SendPaymentNotificationListener
{
    public WalletTransactionRepository $walletTransactionRepository ;

    /**
     * Create the event listener.
     */
    public function __construct( WalletTransactionRepository $walletTransactionRepository)
    {
        $this->walletTransactionRepository =  $walletTransactionRepository ;
    }

    /**
     * Handle the event.
     */
    public function handle(NotifyPaymentEvent $event): void
    {
        $payer_wallet = ($event->payer_wallet instanceof Wallet ) ? $event->payer_wallet  : app(WalletRepository::class)->find($event->payer_wallet)  ;
        $user = $event->user ;
        
        if($payer_wallet){
            
            $transaction = $event->payment->transactions->first(function ($transaction)  use ($payer_wallet) {
                return  $transaction->user_id == $payer_wallet->user->id;
            }) ;

            if($transaction && $transaction->action == PaymentType::DEBIT->value){
                Log::info(['NotifyPaymentEvent', 'Payement : Type '. $transaction->action. ' → montant de '.$transaction->amount.' débité du compte  de '.$payer_wallet->user->name]) ;
                try{
                    Notification::send([$payer_wallet->user], new NewDebitPayment($transaction));
                } catch (\Exception $e) {
                    Log::error("Erreur dans SendPaymentNotificationListener avec l'envoie de notifications: " . $e->getMessage());
                }
            }
            

        }
        
        if($user){
            Log::info(['NotifyPaymentEvent', 'Paiement : Type  crédité sur le compte. Venant de ']) ;
                    
            if(!is_null($user)){ 
                $transaction = $event->payment->transactions->first(function ($transaction)  use ($user) {
                    return  $transaction->user_id == $user->id;
                }) ;

                if($transaction && $transaction->action == PaymentType::CREDIT->value){
                    Log::info(['NotifyPaymentEvent', 'Paiement : Type ' . $transaction->action . ' → montant '.$transaction->amount.' crédité sur le compte de '.$user->name]) ;
                    try{
                        // Notifier le bénéficiaire (user), et non le payeur
                        Notification::send([$user], new NewReceivedPayment($transaction));
                    } catch (\Exception $e) {
                        Log::error("Erreur dans SendPaymentNotificationListener avec l'envoie de notifications: " . $e->getMessage());
                    }
                }
            } 
        }

    }
}
