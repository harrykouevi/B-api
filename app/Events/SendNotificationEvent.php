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

class SendNotificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public \Illuminate\Support\Collection|array $notifiables;
    /**
     * Create a new event instance.
     */
    public function __construct(\Illuminate\Support\Collection|array $notifiables, $user )
    {
        

        $this->notifiables = $notifiables;
     
    }

}
