<?php
/*
 * File name: PayStackController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayStackController extends ParentBookingController
{

    public function __init()
    {

    }

    public function index(): View
    {
        return view('home');
    }

    public function checkout(Request $request): RedirectResponse|View
    {
        $this->booking = $this->bookingRepository->findWithoutFail($request->get('booking_id'));
        if (empty($this->booking)) {
            Flash::error("Error processing PayStack payment for your booking");
            return redirect(route('payments.failed'));
        }
        return view('payment_methods.paystack_charge', ['booking' => $this->booking]);
    }

    public function paySuccess(Request $request, int $bookingId, string $reference): JsonResponse|RedirectResponse
    {
        $this->booking = $this->bookingRepository->findWithoutFail($bookingId);

        if (empty($this->booking)) {
            Flash::error("Error processing PayStack payment for your booking");
            return redirect(route('payments.failed'));
        } else {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . setting('paystack_secret'),
                    "Cache-Control: no-cache",
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                return $this->sendError($err);
            } else {
                $this->paymentMethodId = 8; // Paystack method id
                $this->createBooking();
                return $this->sendResponse($response, __('lang.saved_successfully'));
            }
        }
    }
}
