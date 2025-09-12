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
                Log::info(['NotifyPaymentEvent', 'Payement : Type '. $transaction->action. ' → montant de '.$transaction->amount.' débité du compte pour le compte de '.$payer_wallet->user->name]) ;
                Notification::send([$payer_wallet->user], new NewDebitPayment($transaction));
            }
            

        }else if($user){
            
            if(!is_null($user)){ 
                $transaction = $event->payment->transactions->first(function ($transaction)  use ($user) {
                    return  $transaction->user_id == $user->id;
                }) ;

                if($transaction && $transaction->action == PaymentType::CREDIT->value){
                    Log::info(['NotifyPaymentEvent', 'Paiement : Type ' . $transaction->action . ' → montant de'.$transaction->amount.' crédité sur le compte. Venant de '.$user->name]) ;
                    Notification::send([$payer_wallet->user], new NewReceivedPayment($transaction));
                }
            } 
        }

    }
}
