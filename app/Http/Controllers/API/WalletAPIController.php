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
use App\Repositories\PaymentMethodRepository;
use App\Repositories\WalletRepository;
use App\Services\CinetPayService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Log;


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

    private CinetPayService $cinetPayService;
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(CinetPayService $cinetPayService, PaymentService $paymentService, WalletRepository $walletRepo, CurrencyRepository $currencyRepository,PaymentMethodRepository $paymentMethodRepository)
    {
        parent::__construct();
        $this->walletRepository = $walletRepo;
        $this->currencyRepository = $currencyRepository;
        $this->paymentService = $paymentService;
        $this->cinetPayService = $cinetPayService;
        $this->paymentMethodRepository = $paymentMethodRepository;

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
            return $this->sendError(array_values($e->errors()), 422);
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
            $resp = $this->paymentService->createPayment(auth()->user()->hasRole('customer') ? 0 : 0, setting('app_default_wallet_id'), auth()->user());


        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()), 422);
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
            $resp = $this->paymentService->createPayment($request->get('amount'), setting('app_default_wallet_id'), auth()->user());
            //ici c'est une transaction de l'exterieeur de l'app vers un compte
            $resp = $this->paymentService->makeDeposit($request->get('amount'), $wallet);

            $input = [];
            $input['balance'] = $wallet->balance + $request->get('amount');
            $wallet = $this->walletRepository->update($input, $id);
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()), 422);
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
    public function sendNotification(): JsonResponse
    {
        try {
            Log::error(['sendNotification', auth()->user()]);

            Notification::send(auth()->user(), "yes yes yes yes");

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(true, __('lang.deleted_successfully', ['operator' => __('lang.wallet')]));

    }

    private function validateRechargeRequest(Request $request, string $paymentChannel): ?JsonResponse
    {
        $maxAllowed = 100000;

        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => [
                'required',
                'numeric',
                "min:100",
                "max:$maxAllowed",
                function ($attribute, $value, $fail) {
                    if ($value % 5 !== 0) {
                        $fail("Le montant doit être un multiple de 5.");
                    }
                },
            ],
        ],
            $rules = [
                'user_id.required' => 'Le champ user_id est obligatoire.',
                'user_id.integer' => 'Le champ user_id doit être un entier.',
                'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
                'amount.required' => 'Le champ amount est obligatoire.',
                'amount.numeric' => 'Le champ amount doit être un nombre.',
                'amount.min' => "Le montant doit être au moins 5.",
                'amount.max' => "Le montant ne peut pas dépasser $maxAllowed.",
            ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors()->all(),
            ], 400);
        }
        if ($paymentChannel === 'CREDIT_CARD') {
            $rules = array_merge($rules, [
                'customer_name' => 'required|string',
                'customer_surname' => 'required|string',
                'customer_address' => 'required|string',
                'customer_city' => 'required|string',
                'customer_zip_code' => 'required|string|max:5',
            ]);
        }

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors()->all(),
            ], 422);
        }

        return null;
    }


    private function resolvePaymentChannel(string $methodName): ?string
    {
        $name = strtolower($methodName);

        if (str_contains($name, 'mobile') || str_contains($name, 'money')) {
            return 'MOBILE_MONEY';
        }

        if (str_contains($name, 'card') || str_contains($name, 'credit')|| str_contains($name, 'Credit')|| str_contains($name, 'crédit')|| str_contains($name, 'Carte') || str_contains($name, 'carte')) {
            return 'CREDIT_CARD';
        }

        return null;
    }





    private function buildCustomerData(Request $request, string $paymentChannel, int $userId): array
    {
        log::info("dans la fonction");
        if ($paymentChannel === 'CREDIT_CARD') {
            return [
                'customer_id' => (string) $userId,
                'customer_name' => $request->input('customer_name'),
                'customer_surname' => $request->input('customer_surname'),
                'customer_phone_number' => $request->input('customer_phone_number'),
                'customer_email' => $request->input('customer_email'),
                'customer_address' => $request->input('customer_address'),
                'customer_city' => $request->input('customer_city'),
                'customer_country' => ('TG'),
                'customer_state' => ('TG'),
                'customer_zip_code' => $request->input('customer_zip_code'),
            ];
        }


        log::info("Cas de telephone");
        return [
            'customer_phone_number' => $request->input('phone_number'),

        ];
    }

    //méthode de rechage du wallet(reception des données utilisateur et envoie au service de paiement)
    public function increaseWallet(Request $request): JsonResponse
    {
        try {
            $paymentMethodName = strtolower($request->get('payment_method_name'));
            $paymentChannel = $this->resolvePaymentChannel($paymentMethodName);

            if (!$paymentChannel) {
                return $this->sendError('Méthode de paiement non reconnue', 422);
            }

            $validationError = $this->validateRechargeRequest($request, $paymentChannel);
            if ($validationError) {
                return $validationError;
            }

            $userId = $request->get('user_id');
            $amount = $request->get('amount');

            $paymentMethod = $this->paymentMethodRepository->findByField('route', $paymentChannel)->first();

            if (empty($paymentMethod) || !$paymentMethod->enabled) {
                return $this->sendError('Moyen de paiement invalide ou désactivé', 422);
            }

            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria($userId));
            $wallets = $this->walletRepository->all();

            if ($wallets->isEmpty()) {
                return $this->sendError('Aucun wallet trouvé pour cet utilisateur', 404);
            }

            $transactionId = uniqid('txn_');
            $wallet = $wallets->first();
            $description = "Recharge wallet utilisateur #$transactionId";

            $customerData = $this->buildCustomerData($request, $paymentChannel, $userId);
            Log::info('Sortie dans customerData ', ['user_id' => $userId, 'request' => $request->all()]);


            $notifyUrl = url("/api/recharge/callback/{$userId}");
            log::info("notify Url",['url'=> $notifyUrl]);
            $returnUrl = 'myapp://payment-return';

            log::info("Début d'envoi");
            $response = $this->cinetPayService->initPayment(
                $amount,
                'XOF',
                $transactionId,
                $description,
                $paymentChannel,
                $customerData,
                $notifyUrl,
                $returnUrl
            );
            log::info("reponse",['reponse'=> $response]);

            if (isset($response['data']['payment_url'])) {
                return $this->sendResponse($response, "Recharge effectuée avec succès");
            }

            return $this->sendError('Erreur lors de l\'initialisation du paiement', 500);

        } catch (ValidationException $e) {
            Log::info("Erreur", ['exception' => $e->getMessage()]);
            return $this->sendError(array_values($e->errors()), 422);
        } catch (Exception $e) {
            Log::info("Erruer:", ['exception' => $e->getMessage()]);
            return $this->sendError($e->getMessage(), 500);
        }
    }

}
