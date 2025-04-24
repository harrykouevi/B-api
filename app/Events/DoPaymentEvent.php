<?php

namespace App\Events;

use App\Exceptions\InvalidPaymentInfoException;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class DoPaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $amount;
    public Int|String|Wallet $payer_wallet;
    public User $user;
    /**
     * Create a new event instance.
     */
    public function __construct(Array $paymentInfo )
    {
       
        // if (!isset($paymentInfo["amount"]) && !isset($paymentInfo["payer_wallet"]) && !isset($paymentInfo["user"]) ) {
        //     throw new Exception('Invalid payment information.');
        // }

        // if (!is_numeric($paymentInfo['amount'])) {
        //     throw new Exception('required amount.');
        // }

        // if (!is_int($paymentInfo['payer_wallet']) && !is_string($paymentInfo['payer_wallet']) && !($paymentInfo['payer_wallet'] instanceof Wallet) ) {
        //     throw new Exception('Payer wallet must be an integer or string or Wallet instance.');
        // }

        // if (!($paymentInfo['user'] instanceof User)) {
        //     throw new Exception('User must be an object.');
        // }
        // Cette ligne va valider et throw si problème
        throw new InvalidPaymentInfoException($paymentInfo);

        $this->amount = (int) $paymentInfo['amount'];
        $this->payer_wallet = $paymentInfo['payer_wallet'];
        $this->user = $paymentInfo['user'];
     
    }

}
