<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingReportedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $originalBooking;
    public $newBooking;

    /**
     * BookingReportedEvent constructor.
     * @param Booking $originalBooking
     * @param Booking $newBooking
     */
    public function __construct(Booking $originalBooking, Booking $newBooking)
    {
        $this->originalBooking = $originalBooking;
        $this->newBooking = $newBooking;
    }
}