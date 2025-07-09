<?php
/*
 * File name: ParentBookingController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Events\BookingChangedEvent;
use App\Models\Booking;
use App\Notifications\NewBooking;
use App\Repositories\BookingRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Prettus\Validator\Exceptions\ValidatorException;
use Exception;

abstract class ParentBookingController extends Controller
{
    /** @var  BookingRepository */
    protected BookingRepository $bookingRepository;
    /** @var  PaymentRepository */
    protected PaymentRepository $paymentRepository;
    /** @var  NotificationRepository */
    protected NotificationRepository $notificationRepository;
    /** @var Booking */
    protected Booking $booking;
    /** @var int */
    protected int $paymentMethodId;

    /**
     * BookingAPIController constructor.
     * @param BookingRepository $bookingRepo
     * @param PaymentRepository $paymentRepo
     * @param NotificationRepository $notificationRepo
     */
    public function __construct(BookingRepository $bookingRepo, PaymentRepository $paymentRepo, NotificationRepository $notificationRepo)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->paymentRepository = $paymentRepo;
        $this->notificationRepository = $notificationRepo;
        $this->booking = new Booking();

        $this->__init();
    }

    abstract public function __init();

    protected function createBooking(): void
    {
        try {
            $payment = $this->createPayment();
            if ($payment != null) {
                $this->bookingRepository->update(['payment_id' => $payment->id], $this->booking->id);
                event(new BookingChangedEvent($this->booking));
                $this->sendNotificationToProviders();
            }
        } catch (ValidatorException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    protected function createPayment(): mixed
    {
        if ($this->paymentMethodId != null) {
            $input['amount'] = $this->booking->getTotal();
            $input['description'] = trans("lang.done");
            $input['payment_status_id'] = 2;
            $input['payment_method_id'] = $this->paymentMethodId;
            $input['user_id'] = $this->booking->user_id;
            try {
                return $this->paymentRepository->create($input);
            } catch (ValidatorException $e) {
                Log::error($e->getMessage());
            }
        }
        return null;
    }

    protected function sendNotificationToProviders(): void
    {
        try {
                Log::error(['sendNotificationToProviders',$this->booking->salon->users]);

            Notification::send($this->booking->salon->users, new NewBooking($this->booking));

        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

}
