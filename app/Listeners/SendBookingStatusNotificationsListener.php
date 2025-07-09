<?php
/*
 * File name: SendBookingStatusNotificationsListener.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Listeners;

use App\Notifications\StatusChangedBooking;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class SendBookingStatusNotificationsListener
 * @package App\Listeners
 */
class SendBookingStatusNotificationsListener
{

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        try{

            if ($event->booking->at_salon) {
                if ($event->booking->bookingStatus->order < 20) {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                } else if ($event->booking->bookingStatus->order >= 20 && $event->booking->bookingStatus->order < 40) {
                    Notification::send($event->booking->salon->users, new StatusChangedBooking($event->booking));
                } else {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                }
            } else {
                if ($event->booking->bookingStatus->order < 40) {
                    Notification::send([$event->booking->user], new StatusChangedBooking($event->booking));
                } else {
                    Notification::send($event->booking->salon->users, new StatusChangedBooking($event->booking));
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
