<?php

namespace App\Listeners;

use App\Events\DoPaymentEvent;
use App\Models\User;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateBookingPaymentListener
{
     /**
     * @var BookingRepository
     */
    private BookingRepository $bookingRepository;

     /**
     * @var WalletRepository
     */
    private WalletRepository $walletRepository;

    /**
     * Create the event listener.
     *
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository , WalletRepository $walletRepository)
    {
        $this->bookingRepository = $bookingRepository ;
        $this->walletRepository = $walletRepository ;
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            /** @var Booking $booking */
            $booking = $event->booking;
           
            $payments =[];
            if($booking->booking_status_id == 7 && $booking->payment->payment_status_id != 3){
                //refund coiffeur
                if(auth()->user()->hasRole('salon owner') ){
                   
                    $payerW = $this->walletRepository->findByField('user_id',  auth()->user()->id)->first() ;
                    if($payerW == Null) throw new \Exception('user dont have a wallet yet');
                     //le coiffeur rembourse  l'appli
                    array_push($payments ,  ["amount"=>10,"payer_wallet"=>$payerW, "user"=> new User()] );
                    //refund appli
                    array_push($payments ,  ["amount"=>$booking->payment->amount +10,"payer_wallet"=>setting('app_default_wallet_id'), "user"=> $booking->user] );
                }
                if(auth()->user()->hasRole('customer') ){
                    //refund appli
                    array_push($payments ,  ["amount"=>$booking->payment->amount,"payer_wallet"=> setting('app_default_wallet_id'), "user"=> $booking->user] );
                }
            }

            if(!empty($payments)){
                foreach ($payments as $value) {
                    event(new DoPaymentEvent($value));
                }
            }

        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('listeners_transactions')->error('Erreur lors remboursement de la reservation', [
                'exception' => $e,
                'transaction' => $event->booking,
            ]);
        }
    }
}
