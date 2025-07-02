<?php
/*
 * File name: WalletAPIController.php
 * Last modified: 2024.04.10 at 14:21:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Criteria\Wallets\CurrentCurrencyWalletsCriteria;
use App\Criteria\Wallets\EnabledCriteria;
use App\Criteria\Wallets\WalletsOfUserCriteria;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Wallet;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;


/**
 * Class WalletController
 * @package App\Http\Controllers\API
 */
class WalletAPIController extends Controller
{
    /** @var  WalletRepository */
    private WalletRepository $walletRepository;

    /**  @var  CurrencyRepository */
    private CurrencyRepository $currencyRepository;

     /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService ,WalletRepository $walletRepo, CurrencyRepository $currencyRepository )
    {
        parent::__construct();
        $this->walletRepository = $walletRepo;
        $this->currencyRepository = $currencyRepository;
        $this->paymentService =  $paymentService ;

    }

    /**
     * Display a listing of the Wallet.
     * GET|HEAD /wallets
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->walletRepository->pushCriteria(new RequestCriteria($request));
            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
            $this->walletRepository->pushCriteria(new CurrentCurrencyWalletsCriteria());
            $this->walletRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $wallets = $this->walletRepository->orderBy('wallets.balance', 'desc')->all();

        return $this->sendResponse($wallets->toArray(), 'Wallets retrieved successfully');
    }

    /**
     * Store a newly created Wallet in storage.
     * POST /notifications
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'name' => Wallet::$rules['name'],
            ]);
            $currency = $this->currencyRepository->findWithoutFail(setting('default_currency_id'));
            if (empty($currency)) {
                return $this->sendError('Default Currency not found');
            }
            $input = [];
            $input['name'] = $request->get('name');
            $input['currency'] = $currency;
            $input['user_id'] = auth()->id();
            $input['balance'] = 0;
            $input['enabled'] = 1;
            $wallet = $this->walletRepository->create($input);
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        }
        return $this->sendResponse($wallet->toArray(), __('lang.saved_successfully', ['operator' => __('lang.wallet')]));
    }


    /**
     * Store a newly created Wallet in storage.
     * POST /notifications
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function storeDefault(): JsonResponse
    { 
        try {
            $resp = $this->paymentService->createPayment(auth()->user()->hasRole('customer') ? 0 : 0 ,setting('app_default_wallet_id'),auth()->user());
            
        
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
           
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($resp[1]->toArray(), __('lang.saved_successfully', ['operator' => __('lang.wallet')]));
    }

  

    /**
     * Add amount to wallet
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function deposit($id, Request $request): JsonResponse
    { 
        $this->walletRepository->pushCriteria(new EnabledCriteria());
        $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
        $wallet = $this->walletRepository->findWithoutFail($id);
        if (empty($wallet)) {
            return $this->sendError('Wallet not found');
        }

        try {
            $this->validate($request, [
                'amount' => 'required|numeric|min:0.01',
            ]);

          
            //avec ce code c'est une transaction de compte à compte
            $resp = $this->paymentService->createPayment( $request->get('amount') ,setting('app_default_wallet_id'),auth()->user());
            //ici c'est une transaction de l'exterieeur de l'app vers un compte
            $resp = $this->paymentService->makeDeposit( $request->get('amount'), $wallet );

            $input = [];
            $input['balance'] = $wallet->balance + $request->get('amount');
            $wallet = $this->walletRepository->update($input, $id);
              } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
           
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($resp[1]->toArray(), __('lang.saved_successfully', ['operator' => __('lang.wallet')]));
    }

    /**
     * Update the specified Notification in storage.
     *
     * @param $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        try {
            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
            $wallet = $this->walletRepository->findWithoutFail($id);
            if (empty($wallet)) {
                return $this->sendError('Wallet not found');
            }
            $this->validate($request, [
                'name' => Wallet::$rules['name'],
            ]);
            $input = [];
            $input['name'] = $request->get('name');
            $wallet = $this->walletRepository->update($input, $id);
        } catch (ValidatorException|ValidationException|RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($wallet->toArray(), __('lang.saved_successfully', ['operator' => __('lang.wallet')]));
    }

    /**
     * Remove the specified Favorite from storage.
     *
     * @param $id
     *
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
            $wallet = $this->walletRepository->findWithoutFail($id);
            if (empty($wallet)) {
                return $this->sendError('Wallet not found');
            }
            if ($this->walletRepository->delete($id) < 1) {
                return $this->sendError('Wallet not deleted');
            }
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(true, __('lang.deleted_successfully', ['operator' => __('lang.wallet')]));

    }


    /**
     * Remove the specified Favorite from storage.
     *
     * @param $id
     *
     * @return JsonResponse
     */
    public function sendNotification() : JsonResponse
    {
        try {
           
                Notification::send(auth()->user(), "yes yes yes yes");
          
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(true, __('lang.deleted_successfully', ['operator' => __('lang.wallet')]));

    }
}
