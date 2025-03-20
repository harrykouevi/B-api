<?php
/*
 * File name: BookingChangedEvent.php
 * Last modified: 2022.02.16 at 17:42:22
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2022
 */

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Booking $booking;

    /**
     * BookingChangedEvent constructor.
     * @param $booking
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }


}
