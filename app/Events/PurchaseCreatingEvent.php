<?php
/*
 * File name: PurchaseCreatingEvent.php
 * Last modified: 2025.08.28 at 17:30:50
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Purchase;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseCreatingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Purchase $purchase)
    {
        if (!empty($purchase->purchase_at)) {
            $purchase->purchase_at = convertDateTime($purchase->purchase_at);
        }
        if (!empty($purchase->start_at)) {
            $purchase->start_at = convertDateTime($purchase->start_at);
        }
        if (!empty($purchase->ends_at)) {
            $purchase->ends_at = convertDateTime($purchase->ends_at);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|PrivateChannel|array
     */
    public function broadcastOn():  Channel|PrivateChannel|array
   
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
