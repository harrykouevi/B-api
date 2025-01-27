<?php
/*
 * File name: SalonChangedEvent.php
 * Last modified: 2024.04.18 at 17:30:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Events;

use App\Models\Salon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalonChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Salon $newSalon;

    public Salon $oldSalon;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Salon $newSalon, Salon $oldSalon)
    {
        //
        $this->newSalon = $newSalon;
        $this->oldSalon = $oldSalon;
    }

}
