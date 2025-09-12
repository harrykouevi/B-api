<?php

namespace App\Listeners;

use App\Events\DoPaymentEvent;
use App\Events\NotifyPaymentEvent;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreatingPaymentListener
{
     /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * Create the event listener.
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService =  $paymentService ;
    }

    /**
     * Handle the event.
     */
    public function handle(DoPaymentEvent $event): void
    {
        try {
            if($event->amount > 0 ){
                Log::channel('listeners_transactions')->debug('Ceci est un message $this->paymentService->createPayment( montant='. $event->amount.' , user= '.$event->user->id);

                $payment = $this->paymentService->createPayment($event->amount,$event->payer_wallet,$event->user , $event->walletType );
                $payment = $payment[0];      
            }
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('listeners_transactions')->error('Erreur lors du paiement à l\'utilisateur #' . $event->user->id, [
                'exception' => $e,
                'transaction' => $event->amount,
            ]);
        }
    }
}
