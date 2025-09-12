<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Types\WalletType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyPaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;
    public Int|String|Wallet $payer_wallet;
    public User $user;
    public WalletType|Null $walletType;
    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment ,Int|String|Wallet $payer_wallet , User $user )
    {
        $this->payment = $payment;
        $this->payer_wallet = $payer_wallet;
        $this->user = $user;
     
    }

   
}
