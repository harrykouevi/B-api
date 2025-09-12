<?php
/*
 * File name: EventServiceProvider.php
 * Last modified: 2024.04.18 at 17:21:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Providers;

use App\Events\BookingPaymentUpdatedEvent;
use App\Events\BookingStatusChangedEvent;
use App\Events\DoPaymentEvent;
use App\Events\NotifyPaymentEvent;
use App\Events\PaymentUpdatedEvent;
use App\Events\SendEmailOtpEvent;
use App\Events\WalletTransactionCreatedEvent;
use App\Listeners\CreatedWalletTransactionListener;
use App\Listeners\CreatingPaymentListener;
use App\Listeners\DebitCustomerForService;
use App\Listeners\NotifyForBookingPaymentListener;
use App\Listeners\UpdateBookingPaymentListener;
use App\Listeners\SendBookingStatusNotificationsListener;
use App\Listeners\SendEmailOtpEventListener;
use App\Listeners\SendPaymentNotificationListener;
use App\Listeners\UpdateBookingEarningTable;
use App\Listeners\UpdatePaymentListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\SalonChangedEvent' => [
            'App\Listeners\UpdateSalonEarningTableListener',
            'App\Listeners\ChangeCustomerRoleToSalon',
        ],
        'App\Events\UserRoleChangedEvent' => [

        ],
        WalletTransactionCreatedEvent::class => [
            CreatedWalletTransactionListener::class,
        ],
        DoPaymentEvent::class => [
            CreatingPaymentListener::class,
        ],

        BookingPaymentUpdatedEvent::class => [
            UpdateBookingPaymentListener::class,
            SendBookingStatusNotificationsListener::class,
            // UpdateBookingEarningTable::class,
        ],
        
        BookingStatusChangedEvent::class => [
            UpdateBookingPaymentListener::class, //met a jour les information  de paymemnt lié au changement de status chez le booking
            SendBookingStatusNotificationsListener::class, //envoi à qui de droit une notification au changement de status du le booking
        ],
       
        SendEmailOtpEvent::class => [
            SendEmailOtpEventListener::class,
        ],

        NotifyPaymentEvent::class => [
            SendPaymentNotificationListener::class
        ],

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        //
    }
}
