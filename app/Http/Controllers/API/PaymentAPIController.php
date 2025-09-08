<?php
/*
 * File name: PaymentAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;

use App\Criteria\Bookings\BookingsOfUserCriteria;
use App\Events\BookingChangedEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Log;

use App\Notifications\StatusChangedPayment;
use App\Repositories\BookingRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\PaymentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Criteria\Wallets\EnabledCriteria;
use App\Criteria\Wallets\WalletsOfUserCriteria;
use App\Models\Purchase;
use App\Repositories\PurchaseRepository;
use App\Types\WalletType;

/**
 * Class PaymentController
 * @package App\Http\Controllers\API
 */
class PaymentAPIController extends Controller
{
    /** @var  PaymentRepository */
    private PaymentRepository $paymentRepository;
    /**
     * @var BookingRepository
     */
    private BookingRepository $bookingRepository;

      /**
     * @var PurchaseRepository
     */
    private PurchaseRepository $purchaseRepository;

    /**
     * @var WalletTransactionRepository
     */
    private WalletTransactionRepository $walletTransactionRepository;
    /**
     * @var WalletRepository
     */
    private WalletRepository $walletRepository;

    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService ,PaymentRepository $paymentRepo, BookingRepository $bookingRepo,PurchaseRepository $purchaseRepository , WalletTransactionRepository $walletTransactionRepository, WalletRepository $walletRepository)
    {
        parent::__construct();
        $this->paymentRepository = $paymentRepo;
        $this->bookingRepository = $bookingRepo;
        $this->purchaseRepository = $purchaseRepository;
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->walletRepository = $walletRepository;
        $this->paymentService =  $paymentService ;
    }

    /**
     * Display a listing of the Payment.
     * GET|HEAD /payments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->paymentRepository->pushCriteria(new RequestCriteria($request));
            $this->paymentRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $payments = $this->paymentRepository->all();

        return $this->sendResponse($payments->toArray(), 'Payments retrieved successfully');
    }

    /**
     * Store a newly created Payment in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cash(Request $request): JsonResponse
    {
        $input = $request->all();
        try {
            // $booking = $this->bookingRepository->find($input['id']);
            // $input['payment']['amount'] = $booking->getTotal();
            // $input['payment']['description'] = __('lang.payment_booking_id') . $input['id'];
            // $input['payment']['payment_status_id'] = 1;
            // $input['payment']['user_id'] = $booking->user_id;
            // $payment = $this->paymentRepository->create($input['payment']);
            // $booking = $this->bookingRepository->update(['payment_id' => $payment->id], $input['id']);
            $transactionAmount= $input['payment']['amount'] ;
            try{
                // $wallet = $this->walletRepository->find($walletId);
                $this->walletRepository->pushCriteria(new EnabledCriteria());
                $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
                $wallet = $this->walletRepository->all()->first(function ($wallet) use ($transactionAmount) {
                    // Si wallet "bonus" avec fonds suffisants
                    if ($wallet->name === WalletType::BONUS->value && $wallet->balance >= $transactionAmount) {
                        return true;
                    }

                    // Si wallet "igris" avec beaucoup plus de fonds
                    if ($wallet->name === WalletType::PRINCIPAL->value && $wallet->balance >= $transactionAmount) {
                        return true;
                    }

                    return false;
                });

                if(is_null($wallet) ) return $this->sendError(__('lang.wallet_insufficient_amount', ['operator' => __('lang.wallet')]));

                $currency = json_decode($wallet->currency, true);
                $booking = $this->bookingRepository->find($input['id']);
                

                if ($wallet && $currency['code'] == setting('default_currency_code')) {
                   
                    $payment = $this->paymentService->createPayment($transactionAmount,$wallet);
                    $payment = $payment[0];
                    if($payment){
                        
                        try{ 
                            $booking = $this->bookingRepository->update(['payment_id' => $payment->id], $input['id']);
                            $purchase = $this->purchaseRepository->Create([
                                'salon' => $booking->salon ,
                                'booking' => $booking,
                                'e_services' => $booking->e_services ,
                                'quantity' => $booking->quantity,
                                'user_id' => $booking->user_id ,
                                'taxes'=>  $booking->purchase_taxes ,
                                'purchase_status_id' => 1 ,
                                'hint' => 'cash' ,
                                'purchase_at'  => now()  
                            ]);

                            Notification::send($booking->salon->users, new StatusChangedPayment($booking));
                        } catch (Exception $e) {
                            Log::error($e->getMessage());
                        }
                    }

                } else {
                    return $this->sendError(__('lang.not_found', ['operator' => __('lang.wallet')]));
                }

                Notification::send($booking->salon->users, new StatusChangedPayment($booking));

            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

        } catch (ValidatorException) {
            return $this->sendError(__('lang.not_found', ['operator' => __('lang.payment')]));
        }

        return $this->sendResponse($payment->toArray(), __('lang.saved_successfully', ['operator' => __('lang.payment')]));
    }

    /**
     * Update the specified Payment in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        // todo payment of provider
        $payment = $this->paymentRepository->findWithoutFail($id);
        if (empty($payment)) {
            return $this->sendError(__('lang.not_found', ['operator' => __('lang.payment')]));
        }
        $input = $request->except('amount', 'payment_method_id', 'id');
        try {
            $this->paymentRepository->update($input, $id);
            $payment = $this->paymentRepository->with(['paymentMethod', 'paymentStatus'])->find($id);
            try{
                Log::error(['PaymentAPIController-update',$payment->booking->user]);

                Notification::send([$payment->booking->user], new StatusChangedPayment($payment->booking));

            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
            
            event(new BookingChangedEvent($payment->booking));
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($payment->toArray(), __('lang.saved_successfully', ['operator' => __('lang.booking')]));
    }

    /**
     * Store a newly created Payment in storage.
     *
     * @param string $walletId
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function wallets(string $walletId, Request $request): JsonResponse
    {
        $input = $request->all();
        $transaction = [];
        try {
            $wallet = $this->walletRepository->find($walletId);
            $currency = json_decode($wallet->currency, true);
            
            
            $booking = $this->bookingRepository->find($input['id']);
            $servicesAmountIntentToDebit = $booking->getSubtotal();
           

            $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
            $waitingAmountToDebit = $this->bookingRepository->findByField('booking_status_id', 1)->sum(function ($booking) {
                                        return $booking->getSubtotal();
                                    });
            Log::info([$wallet->id , $currency['code'] , setting('default_currency_code') ,$servicesAmountIntentToDebit , $wallet->balance]);
            
            
            if ($wallet && $currency['code'] == setting('default_currency_code')) {

                //si le montant de la reservation +montant nouvelle achat + montant achat precedent est inferieur ou egales au montant sur le wallet
                if(($input['payment']['amount'] + $servicesAmountIntentToDebit + $waitingAmountToDebit) >  $wallet->balance ) return $this->sendError(__('lang.wallet_insufficient_amount', ['operator' => __('lang.wallet')]));
                    //permettre le payment pour cette reservation sinon dire que ca ne peut se faire car il n'y a pas suffisemment d'agent sur le wallet
                Log::info(['PaymentAPIController-wallet 1']);
                $payment = $this->paymentService->createPayment($input['payment']['amount'],$wallet);
                $payment = $payment[0];
                if($payment){
                    $booking = $this->bookingRepository->update(['payment_id' => $payment->id], $input['id']);
                    try{ 
                        Log::info(['PaymentAPIController-wallet',$booking->salon->users]);

                        Notification::send($booking->salon->users, new StatusChangedPayment($booking));
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                    }
                }

            } else {
                return $this->sendError(__('lang.not_found', ['operator' => __('lang.wallet')]));
            }
        } catch (ValidatorException|ModelNotFoundException) {
            return $this->sendError(__('lang.not_found', ['operator' => __('lang.payment')]));
        } catch (Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse(!is_null($payment)? $payment->toArray() : $payment, __('lang.saved_successfully', ['operator' => __('lang.payment')]));
    }

    public function byMonth(): JsonResponse
    {
        $payments = [];
        if (!empty($this->paymentRepository)) {
            $payments = $this->paymentRepository->orderBy("created_at")->all()->map(function ($row) {
                $row['month'] = $row['created_at']->format('M');
                return $row;
            })->groupBy('month')->map(function ($row) {
                return number_format((float)$row->sum('amount'), setting('default_currency_decimal_digits', 2), '.', '');
            });
        }

        return $this->sendResponse([array_values($payments->toArray()), array_keys($payments->toArray())], 'Payment retrieved successfully');
    }
}
