<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendOtpByInfoBipEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $code ;
    public string $phoneNumber ;
    public string $provider ;

    /**
     * Create a new event instance.
     */
    public function __construct(string $code , string $phoneNumber , string $provider = "sms")
    {
        $this->code = $code ;
        $this->phoneNumber = $phoneNumber ;
        $this->provider = $provider ;
    }

   
}
