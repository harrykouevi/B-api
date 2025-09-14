<?php

namespace App\Events;

use App\Exceptions\InvalidPaymentInfoException;
use App\Models\Tax;
use App\Models\User;
use App\Models\Wallet;
use App\Types\WalletType;
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
    public WalletType|Null $walletType;
    public Tax|array|null $taxes;
    /**
     * Create a new event instance.
     */
    public function __construct(Array $paymentInfo )
    {
    
        InvalidPaymentInfoException::check($paymentInfo);

        $this->amount = (int) $paymentInfo['amount'];
        $this->payer_wallet = $paymentInfo['payer_wallet'];
        $this->user = !is_null($paymentInfo['user'])? $paymentInfo['user'] : new User();
        $this->walletType = array_key_exists('walletType',$paymentInfo)? $paymentInfo['walletType'] : Null;
        $this->taxes =  array_key_exists('taxes',$paymentInfo)? $paymentInfo['taxes'] : Null;
     
    }

}
