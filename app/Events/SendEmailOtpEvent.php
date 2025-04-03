<?php

namespace App\Events;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class SendEmailOtpEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public User $user;
    /**
     * Create a new event instance.
     */
    public function __construct(User $user )
    {
        if (is_null($user->email) ) {
            throw new InvalidArgumentException('Invalid Email address information.');
        }


        $this->user = $user;
     
    }

}
