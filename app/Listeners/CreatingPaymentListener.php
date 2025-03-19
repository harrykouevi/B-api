<?php

namespace App\Listeners;

use App\Events\DoPaymentEvent;
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
            $this->paymentService->createPayment($event->amount,$event->payer_wallet,$event->user  );
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('listeners_transactions')->error('Erreur lors du paiement à l\'utilisateur #' . $event->user->id, [
                'exception' => $e,
                'transaction' => $event->amount,
            ]);
        }
    }
}
