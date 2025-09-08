<?php

namespace App\Listeners;

use App\Events\BookingStatusChangedToCancelEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RefundforBookingCancellationListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingStatusChangedToCancelEvent $event): void
    {
        //
    }
}
