<?php
/*
 * File name: Stripe1piController.php
 * Last modified: 202R.03.15 at 11:47:27
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

namespace App\Http\Controllers\Api;

use Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use App\Http\Controllers\ParentBookingController;


class StripeApiController extends ParentBookingController
{

    private string $stripePaymentMethodId;

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
            Flash::error("Error processing Stripe payment for your booking");
            return redirect(route('payments.failed'));
        }
        return view('payment_methods.stripe_charge', ['booking' => $this->booking]);
    }

    public function paySuccess(Request $request, int $bookingId, string $paymentMethodId): RedirectResponse|JsonResponse
    {
        $this->booking = $this->bookingRepository->findWithoutFail($bookingId);
        $this->stripePaymentMethodId = $paymentMethodId;

        if (empty($this->booking)) {
            Flash::error("Error processing Stripe payment for your booking");
            return redirect(route('payments.failed'));
        } else {
            try {
                $stripeCart = $this->getBookingData();
                $intent = PaymentIntent::create($stripeCart);
                $intent = PaymentIntent::retrieve($intent->id);
                $intent = $intent->confirm();
                Log::info($intent->status);
                if ($intent->status == 'succeeded') {
                    $this->paymentMethodId = 7; // Stripe method
                    $this->createBooking();
                }
                return $this->sendResponse($intent, __('lang.saved_successfully'));
            } catch (ApiErrorException $e) {
                return $this->sendError($e->getMessage());
            }
        }
    }

    /**
     * Set cart data for processing payment on Stripe.
     */
    private function getBookingData(): array
    {
        $data = [];
        $amount = $this->booking->getTotal();
        $data['amount'] = (int)($amount * 100);
        $data['payment_method'] = $this->stripePaymentMethodId;
        $data['currency'] = setting('default_currency_code');

        return $data;
    }

    public function topUpWallet(Request $request)
    {
        // Montant à ajouter au wallet
        $amount = $request->input('amount');

        // Frais Stripe pour l'alimentation
        $stripeFee = (int)($amount * 0.029) + 30; // 2.9% + 0.30$

        // Frais utilisateur pour l'alimentation
        $userFee = 2;

        // Création du PaymentIntent pour l'alimentation
        $paymentIntent = PaymentIntent::create([
            'amount' => (int)($amount * 100), // Conversion en centimes
            'currency' => 'usd',
            'payment_method' => 'pm_123', // ID de la méthode de paiement
        ]);

        // Confirmer le paiement
        $paymentIntent->confirm();

        // Mise à jour du wallet interne
        $user = auth()->user();
        $wallet = $user->wallet;
        $wallet->balance += $amount - $stripeFee - $userFee;
        $wallet->save();

        // Ajout des frais utilisateur à votre compte
        // ...
    }

    public function withdraw(Request $request)
    {
        // Montant à retirer
        $amount = $request->input('amount');

        // Frais Stripe pour le retrait
        $stripeFee = (int)($amount * 0.029) + 30; // 2.9% + 0.30$

        // Frais utilisateur pour le retrait
        $userFee = 1;

        // Vérification des fonds suffisants dans le wallet
        if ($request->user()->wallet->balance >= $amount + $stripeFee + $userFee) {
            // Débit du wallet interne
            $wallet = $request->user()->wallet;
            $wallet->balance -= $amount + $stripeFee + $userFee;
            $wallet->save();

            // Création d'un PaymentIntent pour le retrait (si nécessaire)
            // Dans ce cas, nous supposons que le retrait se fait via un virement bancaire ou une autre méthode
            // qui ne nécessite pas un PaymentIntent Stripe pour le retrait lui-même.

            // Ajout des frais utilisateur à votre compte
            // ...
        } else {
            // Gérer l'erreur de fonds insuffisants
        }
    }
}
