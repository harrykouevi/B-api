<?php
/*
 * File name: BookingPaymentUpdatedEvent.php
 * Last modified: 2022.02.16 at 17:42:22
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2022
 */

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingPaymentUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Booking $booking;

    /**
     * BookingPaymentUpdatedEvent constructor.
     * @param $booking
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }


}
