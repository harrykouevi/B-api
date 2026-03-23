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
use App\Models\WalletTransaction;
use App\Models\PaydunyaPaymentRequest;
use App\Repositories\CurrencyRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\WalletRepository;
use App\Services\CinetPayService;
use App\Services\PaydunyaDisbursementService;
use App\Services\PaydunyaCheckoutService;
use App\Services\PaydunyaService;
use App\Services\PaymentService;
use App\Types\PaymentType;
use App\Types\WalletType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


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
    private PaydunyaService $paydunyaService;
    private PaydunyaCheckoutService $paydunyaCheckoutService;
    private PaydunyaDisbursementService $paydunyaDisbursementService;
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(
        // CinetPayService $cinetPayService,
        PaymentService $paymentService,
        PaydunyaService $paydunyaService,
        PaydunyaCheckoutService $paydunyaCheckoutService,
        PaydunyaDisbursementService $paydunyaDisbursementService,
        WalletRepository $walletRepo,
        CurrencyRepository $currencyRepository,
        PaymentMethodRepository $paymentMethodRepository
    ) {
        parent::__construct();
        $this->walletRepository = $walletRepo;
        $this->currencyRepository = $currencyRepository;
        $this->paymentService = $paymentService;
        // $this->cinetPayService = $cinetPayService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paydunyaService = $paydunyaService;
        $this->paydunyaCheckoutService = $paydunyaCheckoutService;
        $this->paydunyaDisbursementService = $paydunyaDisbursementService;
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
            $resp = $this->paymentService->createPayment(Auth::user()->hasRole('customer') ? 0 : 0, setting('app_default_wallet_id'), auth()->user());
            $resp_ = $this->paymentService->createPayment(auth()->user()->hasRole('customer') ? 0 : 0, setting('app_default_wallet_id'), auth()->user(), WalletType::BONUS);
            $wallets = collect([
                $resp[1],
                $resp_[1],
            ]);
            
            return $this->sendResponse($wallets, __('lang.saved_successfully', ['operator' => __('lang.wallet')]));

        } catch (ValidationException $e) {
            Log::info($e->getMessage());

            return $this->sendError(array_values($e->errors()), 422);
        } catch (Exception $e) {
            Log::error($e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError($e->getMessage());
        }
    }


    /**
     * Add amount to wallet
     * @param $id
     * @param Request $request
     * @return JsonResponse|void
     */
    public function deposit($id, Request $request)
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


        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()), 422);
        } catch (Exception $e) {

            return $this->sendError($e->getMessage());
        }
        // return $this->sendResponse($resp[1]->toArray(), __('lang.saved_successfully', ['operator' => __('lang.wallet')]));
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

        $validator = Validator::make($request->all(), [
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

        $validator = Validator::make($request->all(), $rules);

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

        if (str_contains($name, 'card') || str_contains($name, 'credit') || str_contains($name, 'Credit') || str_contains($name, 'crédit') || str_contains($name, 'Carte') || str_contains($name, 'carte')) {
            return 'CREDIT_CARD';
        }

        return null;
    }


    private function buildCustomerData(Request $request, string $paymentChannel, int $userId): array
    {
        log::info("dans la fonction");
        if ($paymentChannel === 'CREDIT_CARD') {
            return [
                'customer_id' => (string)$userId,
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

    private function buildPaydunyaPayload(Request $request): array
    {
        return [
            'recipient_email' => $request->input('customer_email') ?? $request->input('email'),
            'recipient_phone' => $request->input('phone_number') ?? $request->input('customer_phone_number'),
            'support_fees' => (int)$request->input('paydunya_support_fees', config('services.paydunya.support_fees', 1)),
            'send_notification' => (int)$request->input('paydunya_send_notification', config('services.paydunya.send_notification', 0)),
        ];
    }

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

            $userId = (int)$request->get('user_id');
            $amount = (float)$request->get('amount');

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
            $wallet = $wallets->firstWhere('name', 'Igris');

            if (!$wallet) {
                return $this->sendError("Le wallet 'Igris' est introuvable pour cet utilisateur", 404);
            }
            $description = "Recharge wallet utilisateur #$transactionId";

            $customerData = $this->buildCustomerData($request, $paymentChannel, $userId);
            Log::info('Sortie dans customerData ', ['user_id' => $userId, 'request' => $request->all()]);

            $notifyUrl = url("/api/recharge/callback/{$userId}");
            Log::info("notify Url", ['url' => $notifyUrl]);
            $returnUrl = route('payments.return', ['transaction' => $transactionId]);
            $paydunyaPayload = $this->buildPaydunyaPayload($request);

            $paymentContext = [
                'wallet' => $wallet,
                'userId' => $userId,
                'amount' => $amount,
                'description' => $description,
                'paymentChannel' => $paymentChannel,
                'customerData' => $customerData,
                'notifyUrl' => $notifyUrl,
                'returnUrl' => $returnUrl,
                'transactionId' => $transactionId,
                'paydunya' => $paydunyaPayload,
            ];

            $paymentResult = $this->initiatePaymentWithFallback($paymentContext);

            if ($paymentResult !== null) {
                return $this->sendResponse($paymentResult['response'], $paymentResult['message']);
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

    /**
     * @param array<string, mixed> $context
     */
    private function initiatePaymentWithFallback(array $context): ?array
    {
        $cinetPayResponse = $this->attemptCinetPay($context);

        if ($cinetPayResponse !== null) {
            return [
                'response' => $cinetPayResponse,
                'message' => "Contact établi avec succès",
            ];
        }

        Log::info('Bascule vers PayDunya après échec CinetPay', [
            'transaction_id' => $context['transactionId'],
        ]);

        $paydunyaResponse = $this->attemptPaydunya($context);

        if ($paydunyaResponse !== null) {
            return [
                'response' => $paydunyaResponse,
                'message' => "Lien de paiement généré avec PayDunya",
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function attemptCinetPay(array $context): ?array
    {
        $cinetPayService = app(cinetPayService::class) ;
        $cinetPayTokenResponse = $cinetPayService->getAuthTokenForPayment();

        if (!isset($cinetPayTokenResponse['success']) || !$cinetPayTokenResponse['success']) {
            Log::warning('CinetPay indisponible', [
                'transaction_id' => $context['transactionId'],
                'cinetpay_error' => $cinetPayTokenResponse['message'] ?? 'Erreur inconnue'
            ]);
            return null;
        }

        try {
            Log::info("Début d'envoi via CinetPay", ['transaction_id' => $context['transactionId']]);
            $response = $cinetPayService->initPayment(
                $context['amount'],
                'XOF',
                $context['transactionId'],
                $context['description'],
                $context['paymentChannel'],
                $context['customerData'],
                $context['notifyUrl'],
                $context['returnUrl']
            );
            Log::info("Réponse CinetPay reçue", ['response' => $response]);
        } catch (Exception $exception) {
            Log::error("Erreur lors de l'appel CinetPay", [
                'transaction_id' => $context['transactionId'],
                'exception' => $exception->getMessage()
            ]);
            return null;
        }

        if (isset($response['data']['payment_url'])) {
            return $response;
        }

        Log::warning('Réponse CinetPay invalide, absence de payment_url', [
            'transaction_id' => $context['transactionId'],
            'response' => $response
        ]);
        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function attemptPaydunya(array $context): ?array
    {
        Log::info("Début d'envoi via PayDunya Checkout (PAR)", ['transaction_id' => $context['transactionId']]);

        try {
            // Créer des items pour la facture
            $items = [
                [
                    'name' => 'Recharge de wallet',
                    'quantity' => 1,
                    'unit_price' => (int) $context['amount'],
                    'total_price' => (int) $context['amount'],
                    'description' => $context['description'],
                ],
            ];

            // Options pour la création de l'invoice
            $options = [
                'description' => $context['description'],
               'callback_url' => env('PAYDUNYA_CALLBACK_URL', url('/api/paydunya/payment/callback')),
                'return_url' => env('PAYDUNYA_RETURN_URL', url('/api/paydunya/payment/success')),
                'cancel_url' => env('PAYDUNYA_CANCEL_URL', url('/api/paydunya/payment/cancel')),
                'custom_data' => [
                    'user_id' => $context['userId'],
                    'wallet_id' => $context['wallet']->id,
                    'transaction_id' => $context['transactionId'],
                ],
            ];

            $response = $this->paydunyaCheckoutService->createInvoice($context['amount'], $items, $options);
            Log::info("Réponse PayDunya Checkout reçue", ['response' => $response]);

            if (!$response['success'] || empty($response['data']['payment_url'])) {
                Log::warning('Réponse PayDunya Checkout invalide', [
                    'transaction_id' => $context['transactionId'],
                    'response' => $response ?? null
                ]);
                return null;
            }

            $token = $response['data']['token'] ?? null;
            if (!$token) {
                Log::warning('Token PayDunya Checkout manquant', [
                    'transaction_id' => $context['transactionId'],
                    'response' => $response,
                ]);
                return null;
            }

            // Enregistrer la demande de paiement avec le token PayDunya
            PaydunyaPaymentRequest::create([
                'user_id' => $context['userId'],
                'wallet_id' => $context['wallet']->id,
                'amount' => $context['amount'],
                'reference_number' => $token, // Utiliser le token comme référence
                'status' => PaydunyaPaymentRequest::STATUS_PENDING,
                'payment_channel' => $context['paymentChannel'],
                'description' => $context['description'],
                'payment_url' => $response['data']['payment_url'] ?? null,
                'payload' => $response['data']['raw'] ?? $response,
            ]);

            return $response;
        } catch (Exception $exception) {
            Log::error("Erreur lors de l'appel PayDunya Checkout", [
                'transaction_id' => $context['transactionId'],
                'exception' => $exception->getMessage()
            ]);
            return null;
        }
    }


    /**
     * Retrait sur wallet avec fallback automatique vers PayDunya en cas d'échec CinetPay
     *
     * @throws RepositoryException
     */
    public function withdrawOnWallet(Request $request): JsonResponse
    {
        Log::info('Début du retrait - Validation des données', ['request_data' => $request->all()]);

        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:500',
            'description' => 'nullable|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'wallet_id' => 'required|string|exists:wallets,id',
            'phone_number' => 'required|string',
            'country_prefix' => 'required|string',
            'payment_method' => 'nullable|string|in:WAVECI,WAVESN',
        ]);

        $userId = (int)$validatedData['user_id'];
        $walletId = $validatedData['wallet_id'];
        $amount = (float)$validatedData['amount'];
        $phoneNumber = $validatedData['phone_number'];
        $countryPrefix = $validatedData['country_prefix'];
        $paymentMethod = $validatedData['payment_method'] ?? null;
        $description = $validatedData['description'] ?? 'Demande de retrait';

        // Validation du wallet et du solde
        $walletValidation = $this->validateWalletForWithdrawal($userId, $walletId, $amount);
        if ($walletValidation['error']) {
            return response()->json($walletValidation['response'], $walletValidation['status']);
        }
        $wallet = $walletValidation['wallet'];

        // Contexte pour les tentatives de retrait
        $withdrawalContext = [
            'userId' => $userId,
            'walletId' => $walletId,
            'wallet' => $wallet,
            'amount' => $amount,
            'phoneNumber' => $phoneNumber,
            'countryPrefix' => $countryPrefix,
            'paymentMethod' => $paymentMethod,
            'description' => $description,
        ];

        // Tentative via CinetPay avec fallback vers PayDunya
        return $this->executeWithdrawalWithFallback($withdrawalContext, $request);
    }

    /**
     * Valide le wallet et le solde pour un retrait
     */
    private function validateWalletForWithdrawal(int $userId, string $walletId, float $amount): array
    {
        Log::info('Vérification du wallet utilisateur', ['user_id' => $userId, 'wallet_id' => $walletId]);

        $this->walletRepository->pushCriteria(new EnabledCriteria());
        $this->walletRepository->pushCriteria(new WalletsOfUserCriteria($userId));
        $wallet = $this->walletRepository->find($walletId);

        if (!$wallet) {
            Log::warning('Wallet non trouvé ou non autorisé', ['user_id' => $userId, 'wallet_id' => $walletId]);
            return [
                'error' => true,
                'status' => 404,
                'response' => ['error' => 'Wallet non trouvé ou non autorisé'],
                'wallet' => null,
            ];
        }

        Log::info('Vérification du solde du wallet', ['wallet_balance' => $wallet->balance, 'requested_amount' => $amount]);

        if (!WalletTransaction::canWithdraw($wallet, $amount)) {
            Log::warning('Montant invalide ou solde insuffisant', [
                'wallet_balance' => $wallet->balance,
                'requested_amount' => $amount,
            ]);
            return [
                'error' => true,
                'status' => 400,
                'response' => [
                    'error' => 'Montant invalide ou solde insuffisant',
                    'message' => 'Le montant doit être un multiple de 5 et supérieur ou égal à 500. Vérifiez également que votre solde est suffisant.',
                ],
                'wallet' => null,
            ];
        }

        return ['error' => false, 'wallet' => $wallet];
    }

    /**
     * Exécute le retrait via CinetPay avec fallback automatique vers PayDunya
     */
    private function executeWithdrawalWithFallback(array $context, Request $request): JsonResponse
    {
        $cinetPayResult = $this->attemptCinetPayWithdrawal($context);

        if ($cinetPayResult['success']) {
            return response()->json($cinetPayResult['response'], 200);
        }

        // Fallback vers PayDunya si CinetPay échoue
        Log::info('Bascule vers PayDunya après échec CinetPay pour le retrait', [
            'user_id' => $context['userId'],
            'amount' => $context['amount'],
            'cinetpay_error' => $cinetPayResult['error_message'] ?? 'Erreur inconnue',
        ]);

        return $this->attemptPaydunyaWithdrawal($context, $request);
    }

    /**
     * Tentative de retrait via CinetPay
     */
    private function attemptCinetPayWithdrawal(array $context): array
    {
        $withdrawal = null;
        $cinetPayService = app(cinetPayService::class) ;

        try {
            $operatorLabel = $context['paymentMethod'] ? " ({$context['paymentMethod']})" : '';
            $description = $context['description'] . $operatorLabel . ' [CinetPay]';

            Log::info('Création de la transaction de retrait CinetPay', [
                'wallet_id' => $context['wallet']->id,
                'user_id' => $context['userId'],
                'amount' => $context['amount'],
            ]);

            $withdrawal = WalletTransaction::createWithdrawal([
                'wallet_id' => $context['wallet']->id,
                'user_id' => $context['userId'],
                'amount' => $context['amount'],
                'description' => $description,
                'status' => WalletTransaction::STATUS_PENDING,
            ]);

            // Vérification du solde CinetPay
            Log::info('Vérification du solde CinetPay', ['amount' => $context['amount']]);
            $balanceResponse = $cinetPayService->checkBalanceAndAuthorizeWithdrawal($context['amount']);

            if (!$balanceResponse['success'] || !$balanceResponse['authorized']) {
                Log::warning('CinetPay: solde insuffisant ou erreur', ['response' => $balanceResponse]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                return [
                    'success' => false,
                    'error_message' => $balanceResponse['message'] ?? 'Solde CinetPay insuffisant',
                    'withdrawal' => $withdrawal,
                ];
            }

            // Exécution du transfert
            Log::info('Exécution du transfert via CinetPay', [
                'withdrawal_id' => $withdrawal->id,
                'phone_number' => $context['phoneNumber'],
            ]);

            $transferResponse = $cinetPayService->executeTransfer(
                $withdrawal,
                $context['phoneNumber'],
                $context['countryPrefix'],
                $context['userId'],
                $context['paymentMethod']
            );

            if (!$transferResponse['success']) {
                Log::warning('CinetPay: échec du transfert', ['response' => $transferResponse]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                return [
                    'success' => false,
                    'error_message' => $transferResponse['message'] ?? 'Erreur lors du transfert CinetPay',
                    'error_code' => $transferResponse['code'] ?? null,
                    'withdrawal' => $withdrawal,
                ];
            }

            // Mise à jour du statut si nécessaire
            $initialTreatmentStatus = $transferResponse['response'][0]['treatment_status'] ?? null;
            if ($initialTreatmentStatus === 'NEW') {
                $withdrawal->update(['status' => WalletTransaction::STATUS_PENDING]);
            }

            Log::info('Retrait CinetPay initié avec succès', [
                'user_id' => $context['userId'],
                'amount' => $context['amount'],
                'transaction_id' => $transferResponse['transaction_id'],
            ]);

            return [
                'success' => true,
                'response' => [
                    'success' => true,
                    'message' => 'Retrait initié avec succès via CinetPay.',
                    'provider' => 'cinetpay',
                    'transaction_id' => $transferResponse['transaction_id'],
                    'client_transaction_id' => $transferResponse['client_transaction_id'],
                    'treatment_status' => $transferResponse['treatment_status'],
                    'sending_status' => $transferResponse['sending_status'],
                    'withdrawal' => $withdrawal,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Exception CinetPay lors du retrait', [
                'error' => $e->getMessage(),
                'user_id' => $context['userId'],
            ]);

            if ($withdrawal instanceof WalletTransaction) {
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
            }

            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'withdrawal' => $withdrawal,
            ];
        }
    }

    /**
     * Tentative de retrait via PayDunya (fallback)
     */
    private function attemptPaydunyaWithdrawal(array $context, Request $request): JsonResponse
    {
        try {
            // Déterminer le mode de retrait :
            // 1. Depuis la requête si fourni
            // 2. Sinon, détecter automatiquement depuis le numéro de téléphone
            $withdrawMode = $request->input('withdraw_mode');
            if (empty($withdrawMode)) {
                $withdrawMode = $this->paydunyaDisbursementService->getWithdrawModeFromPhoneNumber($context['phoneNumber']);
            }

            $callbackUrl = $request->input('callback_url') ?? $this->paydunyaDisbursementService->getDefaultCallbackUrl();

            // Utiliser le numéro de téléphone comme account_alias (sans le préfixe pays)
            $accountAlias = preg_replace('/\D+/', '', $context['phoneNumber']);
            // Enlever le préfixe 228 si présent
            if (str_starts_with($accountAlias, '228')) {
                $accountAlias = substr($accountAlias, 3);
            }

            if (empty($accountAlias)) {
                return response()->json([
                    'error' => 'Numéro de compte invalide pour PayDunya',
                    'message' => 'Le numéro de téléphone fourni est invalide.',
                ], 422);
            }

            Log::info('PayDunya withdraw_mode déterminé', [
                'phone_number' => $context['phoneNumber'],
                'account_alias' => $accountAlias,
                'withdraw_mode' => $withdrawMode,
            ]);

            if (empty($callbackUrl)) {
                // Générer une URL de callback par défaut si non configurée
                $callbackUrl = url('/api/paydunya/disburse/callback');
                Log::warning('Callback URL PayDunya non configurée, utilisation de la valeur par défaut', [
                    'callback_url' => $callbackUrl,
                ]);
            }

            $description = $context['description'] . ' [PayDunya - Fallback]';

            // Créer la transaction de retrait
            $withdrawal = WalletTransaction::createWithdrawal([
                'wallet_id' => $context['wallet']->id,
                'user_id' => $context['userId'],
                'amount' => $context['amount'],
                'description' => $description,
                'status' => WalletTransaction::STATUS_PENDING,
            ]);

            Log::info('💳 [PayDunya PER Fallback] Débit du wallet avant envoi', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $context['amount'],
                'current_balance' => $context['wallet']->balance,
            ]);
            $wallet = $this->walletRepository->findByField('user_id',$context['userId'])->first();
            if(!$wallet){
                return response()->json([
                    'error' => 'Aucun wallet trouvé',
                    'message' => 'le wallet n\'existe pas',
                ], 404);
            }
            
            // Débiter le wallet IMMÉDIATEMENT avant d'envoyer à PayDunya
          $this->paymentService->createPaymentLinkWithExternal(
                (float)$context['amount'],
                $context['wallet'],
                PaymentType::DEBIT
            );

            Log::info('✅ [PayDunya PER Fallback] Wallet débité avec succès', [
                'new_balance' => $context['wallet']->refresh()->balance,
            ]);

            Log::info('Init PayDunya PER (fallback)', [
                'withdrawal_id' => $withdrawal->id,
                'account_alias' => $accountAlias,
                'withdraw_mode' => $withdrawMode,
            ]);

            $invoiceResponse = $this->paydunyaDisbursementService->createInvoice(
                $accountAlias,
                (int)$context['amount'],
                $withdrawMode,
                $callbackUrl,
                (string)$withdrawal->id
            );

            if (!$invoiceResponse['success']) {
                Log::error('🔴 [PayDunya PER Fallback] Échec création invoice, recréditation du wallet');

                // Recréditer le wallet car la demande a échoué

                $wallet->balance  += (float)$context['amount'];
                $wallet->save();
                /*$this->paymentService->createPaymentLinkWithExternal(
                    (float)$context['amount'],
                    $context['wallet'],
                    PaymentType::CREDIT
                );*/

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER Fallback] Wallet recrédité', [
                    'new_balance' => $context['wallet']->refresh()->balance,
                ]);

                return response()->json([
                    'error' => 'Erreur lors de la création du déboursement PayDunya',
                    'message' => $invoiceResponse['message'],
                    'details' => $invoiceResponse['data'] ?? null,
                ], 400);
            }

            $disburseInvoice = $invoiceResponse['data']['disburse_invoice'];
            $withdrawal->description = "{$withdrawal->description} | PayDunya invoice: {$disburseInvoice}";
            $withdrawal->save();

            $submitResponse = $this->paydunyaDisbursementService->submitInvoice($disburseInvoice, (string)$withdrawal->id);

            if (!$submitResponse['success']) {
                Log::error('🔴 [PayDunya PER Fallback] Échec soumission invoice, recréditation du wallet');

                // Recréditer le wallet car la soumission a échoué
                $this->paymentService->createPaymentLinkWithExternal(
                    (float)$context['amount'],
                    $context['wallet'],
                    PaymentType::CREDIT
                );

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER Fallback] Wallet recrédité', [
                    'new_balance' => $context['wallet']->refresh()->balance,
                ]);

                return response()->json([
                    'error' => 'Soumission PayDunya échouée',
                    'message' => $submitResponse['message'],
                    'details' => $submitResponse['data'] ?? null,
                ], 400);
            }

            $status = strtolower($submitResponse['data']['status'] ?? '');
            if ($status === 'success') {
                // Wallet déjà débité, juste mettre à jour le statut
                $withdrawal->update(['status' => WalletTransaction::STATUS_COMPLETED]);

                Log::info('✅ [PayDunya PER Fallback] Retrait complété avec succès');
            } elseif ($status === 'failed') {
                Log::error('🔴 [PayDunya PER Fallback] Retrait échoué, recréditation du wallet');

                // Recréditer le wallet car le retrait a échoué
                $this->paymentService->createPaymentLinkWithExternal(
                    (float)$context['amount'],
                    $context['wallet'],
                    PaymentType::CREDIT
                );

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER Fallback] Wallet recrédité', [
                    'new_balance' => $context['wallet']->refresh()->balance,
                ]);
            }

            Log::info('Retrait PayDunya (fallback) initié avec succès', [
                'user_id' => $context['userId'],
                'amount' => $context['amount'],
                'disburse_invoice' => $disburseInvoice,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Retrait initié avec succès via PayDunya (fallback).',
                'provider' => 'paydunya',
                'status' => $status ?: 'pending',
                'disburse_invoice' => $disburseInvoice,
                'transaction_id' => $submitResponse['data']['transaction_id'] ?? null,
                'provider_ref' => $submitResponse['data']['provider_ref'] ?? null,
                'withdrawal' => $withdrawal->refresh(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Exception PayDunya lors du retrait (fallback)', [
                'error' => $e->getMessage(),
                'user_id' => $context['userId'],
            ]);

            if (isset($withdrawal) && $withdrawal instanceof WalletTransaction) {
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
            }

            return response()->json([
                'error' => 'Erreur lors du traitement du retrait',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function withdrawOnWalletPaydunya(Request $request): JsonResponse
    {
        $supportedModes = $this->paydunyaDisbursementService->getSupportedWithdrawModes();

        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:500',
            'description' => 'nullable|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'wallet_id' => 'required|string|exists:wallets,id',
            'account_alias' => 'required|string|min:5|max:20',
            'withdraw_mode' => ['nullable', Rule::in($supportedModes)],
            'callback_url' => 'nullable|url',
        ]);

        try {
            $userId = (int)$validatedData['user_id'];
            $walletId = $validatedData['wallet_id'];
            $amount = (float)$validatedData['amount'];
            $accountAlias = preg_replace('/\D+/', '', $validatedData['account_alias']);

            // Enlever le préfixe 228 si présent
            if (str_starts_with($accountAlias, '228')) {
                $accountAlias = substr($accountAlias, 3);
            }

            // Déterminer le mode de retrait automatiquement si non fourni
            $withdrawMode = $validatedData['withdraw_mode'] ?? null;
            if (empty($withdrawMode)) {
                $withdrawMode = $this->paydunyaDisbursementService->getWithdrawModeFromPhoneNumber($validatedData['account_alias']);
            }

            $callbackUrl = $validatedData['callback_url'] ?? $this->paydunyaDisbursementService->getDefaultCallbackUrl();
            $description = $validatedData['description'] ?? 'Demande de retrait PayDunya';

            if (empty($accountAlias)) {
                return response()->json([
                    'error' => 'Numéro de compte invalide',
                    'message' => 'Le champ account_alias doit contenir des chiffres.',
                ], 422);
            }

            Log::info('PayDunya withdrawOnWalletPaydunya - withdraw_mode déterminé', [
                'account_alias' => $accountAlias,
                'withdraw_mode' => $withdrawMode,
            ]);

            if (empty($callbackUrl)) {
                // Générer une URL de callback par défaut si non configurée
                $callbackUrl = url('/api/paydunya/disburse/callback');
                Log::warning('Callback URL PayDunya non configurée, utilisation de la valeur par défaut', [
                    'callback_url' => $callbackUrl,
                ]);
            }

            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria($userId));
            $wallet = $this->walletRepository->find($walletId);

            if (!$wallet) {
                return response()->json([
                    'error' => 'Wallet non trouvé ou non autorisé'
                ], 404);
            }

            if (!WalletTransaction::canWithdraw($wallet, $amount)) {
                return response()->json([
                    'error' => 'Montant invalide ou solde insuffisant',
                    'message' => 'Le montant doit être supérieur ou égal à 500 et votre solde doit être suffisant.',
                ], 400);
            }

            // Créer la transaction de retrait
            $withdrawal = WalletTransaction::createWithdrawal([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'amount' => $amount,
                'description' => $description,
                'status' => WalletTransaction::STATUS_PENDING,
            ]);

            Log::info('💳 [PayDunya PER] Débit du wallet avant envoi', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $amount,
                'current_balance' => $wallet->balance,
            ]);

            // Débiter le wallet IMMÉDIATEMENT avant d'envoyer à PayDunya
            $this->paymentService->createPaymentLinkWithExternal(
                (float)$amount,
                $wallet,
                PaymentType::DEBIT
            );

            Log::info('✅ [PayDunya PER] Wallet débité avec succès', [
                'new_balance' => $wallet->refresh()->balance,
            ]);

            Log::info('Init PayDunya PER', [
                'withdrawal_id' => $withdrawal->id,
                'account_alias' => $accountAlias,
                'withdraw_mode' => $withdrawMode,
            ]);

            $invoiceResponse = $this->paydunyaDisbursementService->createInvoice(
                $accountAlias,
                (int)$amount,
                $withdrawMode,
                $callbackUrl,
                (string)$withdrawal->id
            );

            if (!$invoiceResponse['success']) {
                Log::error('🔴 [PayDunya PER] Échec création invoice, recréditation du wallet');

                // Recréditer le wallet car la demande a échoué
                $this->paymentService->createPaymentLinkWithExternal(
                    (float)$amount,
                    $wallet,
                    PaymentType::CREDIT
                );

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER] Wallet recrédité', [
                    'new_balance' => $wallet->refresh()->balance,
                ]);

                return response()->json([
                    'error' => 'Erreur lors de la création du déboursement PayDunya',
                    'message' => $invoiceResponse['message'],
                    'details' => $invoiceResponse['data'] ?? null,
                ], 400);
            }

            $disburseInvoice = $invoiceResponse['data']['disburse_invoice'];
            $withdrawal->description = "{$withdrawal->description} | PayDunya invoice: {$disburseInvoice}";
            $withdrawal->save();

            $submitResponse = $this->paydunyaDisbursementService->submitInvoice($disburseInvoice, (string)$withdrawal->id);

            if (!$submitResponse['success']) {
                Log::error('🔴 [PayDunya PER] Échec soumission invoice, recréditation du wallet');

                // Recréditer le wallet car la soumission a échoué
                $this->paymentService->createPaymentLinkWithExternal(
                    (float)$amount,
                    $wallet,
                    PaymentType::CREDIT
                );

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER] Wallet recrédité', [
                    'new_balance' => $wallet->refresh()->balance,
                ]);

                return response()->json([
                    'error' => 'Soumission PayDunya échouée',
                    'message' => $submitResponse['message'],
                    'details' => $submitResponse['data'] ?? null,
                ], 400);
            }

            $status = strtolower($submitResponse['data']['status'] ?? '');
            if ($status === 'success') {
                // Wallet déjà débité, juste mettre à jour le statut
                $withdrawal->update(['status' => WalletTransaction::STATUS_COMPLETED]);

                Log::info('✅ [PayDunya PER] Retrait complété avec succès');
            } elseif ($status === 'failed') {
                Log::error('🔴 [PayDunya PER] Retrait échoué, recréditation du wallet');

                // Recréditer le wallet car le retrait a échoué
                $this->paymentService->createPaymentLinkWithExternal(
                    (float)$amount,
                    $wallet,
                    PaymentType::CREDIT
                );

                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);

                Log::info('✅ [PayDunya PER] Wallet recrédité', [
                    'new_balance' => $wallet->refresh()->balance,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $submitResponse['message'],
                'status' => $status ?: 'pending',
                'disburse_invoice' => $disburseInvoice,
                'transaction_id' => $submitResponse['data']['transaction_id'] ?? null,
                'provider_ref' => $submitResponse['data']['provider_ref'] ?? null,
                'withdrawal' => $withdrawal->refresh(),
            ], 200);
        } catch (ValidationException $validationException) {
            return $this->sendError(array_values($validationException->errors()), 422);
        } catch (\Exception $exception) {
            Log::error('Erreur retrait PayDunya', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Erreur lors du traitement PayDunya',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    public function getWithdrawalHistory(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $operator = $request->get('operator');

            // Construire la requête de base
            $query = WalletTransaction::with(['wallet', 'user'])
                ->where('user_id', $userId)
                ->where('action', 'retrait')
                ->orderBy('created_at', 'desc');

            // Filtrage par statut
            if ($status) {
                $query->where('status', $status);
            }

            // Filtrage par période
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            // Filtrage par opérateur (basé sur la méthode de paiement)
            if ($operator) {
                // Vous pouvez adapter cette logique selon comment vous stockez l'opérateur
                // Par exemple, si vous l'ajoutez dans la description ou un champ dédié
            }

            // Pagination
            $withdrawals = $query->paginate($perPage);

            // Transformer les données pour inclure plus d'informations
            $withdrawals->getCollection()->transform(function ($withdrawal) {
                return $this->transformWithdrawalData($withdrawal);
            });

            return $this->sendResponse($withdrawals, 'Historique des retraits récupéré avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des retraits', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->sendError('Erreur lors de la récupération de l\'historique des retraits');
        }
    }

    /**
     * Transformer les données de retrait pour l'affichage
     *
     * @param WalletTransaction $withdrawal
     * @return array
     */
    private function transformWithdrawalData(WalletTransaction $withdrawal): array
    {
        $data = $withdrawal->toArray();

        // Ajouter des informations supplémentaires
        $data['status_label'] = $withdrawal->getStatusLabelAttribute();
        $data['operator'] = $this->extractOperatorFromDescription($withdrawal->description);
        $data['cinetpay_transaction_id'] = $withdrawal->payment_id;
        $data['client_transaction_id'] = $this->generateClientTransactionId($withdrawal->id);

        // Extraire les informations CinetPay depuis la description si disponibles
        $cinetpayInfo = $this->extractCinetpayInfoFromDescription($withdrawal->description);
        $data = array_merge($data, $cinetpayInfo);

        return $data;
    }

    /**
     * Extraire l'opérateur depuis la description
     *
     * @param string $description
     * @return string|null
     */
    private function extractOperatorFromDescription(string $description): ?string
    {
        // Vous pouvez adapter cette logique selon comment vous stockez l'opérateur
        if (strpos($description, 'TMONEY') !== false) {
            return 'TMONEY';
        } elseif (strpos($description, 'FLOOZ') !== false) {
            return 'FLOOZ';
        }

        return null;
    }

    /**
     * Extraire les informations CinetPay depuis la description
     *
     * @param string $description
     * @return array
     */
    private function extractCinetpayInfoFromDescription(string $description): array
    {
        $info = [];

        // Extraire l'ID de transaction CinetPay
        if (preg_match('/ID: ([^\)]+)/', $description, $matches)) {
            $info['cinetpay_transaction_id'] = $matches[1];
        }

        // Extraire le statut
        if (preg_match('/Status: ([^|]+)/', $description, $matches)) {
            $info['treatment_status'] = trim($matches[1]);
        }

        if (preg_match('/Sending: ([^|]+)/', $description, $matches)) {
            $info['sending_status'] = trim($matches[1]);
        }

        return $info;
    }

    /**
     * Générer le client_transaction_id à partir de l'ID de transaction
     *
     * @param int $withdrawalId
     * @return string
     */
    private function generateClientTransactionId(int $withdrawalId): string
    {
        // Trouver la transaction CinetPay correspondante pour obtenir le timestamp
        // Vous pouvez stocker le timestamp dans un champ dédié si nécessaire
        return "WD_{$withdrawalId}_" . time();
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('action', 'retrait');
    }

    /**
     * Scope pour filtrer par utilisateur
     *
     * @param $query
     * @param int $userId
     * @return mixed
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour filtrer par période
     *
     * @param $query
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Obtenir les détails d'un retrait spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getWithdrawalDetails(int $id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $withdrawal = WalletTransaction::with(['wallet', 'user'])
                ->where('user_id', $userId)
                ->where('action', 'retrait')
                ->where('id', $id)
                ->first();

            if (!$withdrawal) {
                return $this->sendError('Retrait non trouvé', 404);
            }

            $data = $this->transformWithdrawalData($withdrawal);

            return $this->sendResponse($data, 'Détails du retrait récupérés avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails du retrait', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'withdrawal_id' => $id
            ]);

            return $this->sendError('Erreur lors de la récupération des détails du retrait');
        }
    }


    /**
     * Callback PayDunya Checkout - IPN unifié pour paiements ET retraits
     * Utilisé par checkout (PAR/PSR) et disburse (PER)
     */
    public function handlePaydunyaCheckoutCallback(Request $request): JsonResponse
    {
        // Test d'accessibilité GET
        if ($request->isMethod('GET')) {
            Log::info('✅ [PayDunya Checkout Callback] Test d\'accessibilité (GET) - Réponse OK');
            return response()->json([
                'status' => 'ok',
                'message' => 'PayDunya checkout callback endpoint is accessible',
            ], 200);
        }

        $payload = $request->all();
        Log::info('🔔 [PayDunya Checkout Callback] ========== CALLBACK REÇU ==========');
        Log::info('🔔 [PayDunya Checkout Callback] Payload complet', ['payload' => $payload]);

        // Test d'accessibilité POST avec data=null
        if (isset($payload['data']) && $payload['data'] === null) {
            Log::info('✅ [PayDunya Checkout Callback] Test d\'accessibilité (POST data=null) - Réponse OK');
            return response()->json([
                'status' => 'ok',
                'message' => 'PayDunya checkout callback endpoint is accessible',
            ], 200);
        }

        // Pour l'instant, juste logger et retourner OK
        // TODO: Implémenter la logique de traitement
        Log::info('✅ [PayDunya Checkout Callback] Callback traité - Retour OK');
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Callback PayDunya Disbursement (PER) - IPN pour les retraits
     */
    public function handlePaydunyaDisburseCallback(Request $request): JsonResponse
    {
        // Si c'est une requête GET (test d'accessibilité PayDunya), retourner 200 OK
        if ($request->isMethod('GET')) {
            Log::info('✅ [PayDunya Callback PER] Test d\'accessibilité (GET) - Réponse OK');
            return response()->json([
                'status' => 'ok',
                'message' => 'PayDunya disburse callback endpoint is accessible',
            ], 200);
        }

        $payload = $request->all();
        Log::info('🔔 [PayDunya Callback PER] ========== CALLBACK REÇU DE PAYDUNYA ==========');
        Log::info('🔔 [PayDunya Callback PER] Payload complet', ['payload' => $payload]);

        // Si c'est un test POST avec data=null (test d'accessibilité PayDunya), retourner 200 OK
        if (isset($payload['data']) && $payload['data'] === null) {
            Log::info('✅ [PayDunya Callback PER] Test d\'accessibilité (POST data=null) - Réponse OK');
            return response()->json([
                'status' => 'ok',
                'message' => 'PayDunya disburse callback endpoint is accessible',
            ], 200);
        }

        // Extraction des données du callback
        $token = $payload['token'] ?? null;
        $status = strtolower($payload['status'] ?? '');
        $withdrawMode = $payload['withdraw_mode'] ?? null;
        $amount = $payload['amount'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;
        $disburseId = $payload['disburse_id'] ?? null;
        $disburseTxId = $payload['disburse_tx_id'] ?? null;

        Log::info('📋 [PayDunya Callback PER] Extraction des données', [
            'token' => $token,
            'status' => $status,
            'withdraw_mode' => $withdrawMode,
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'disburse_id' => $disburseId,
        ]);

        if (!$token) {
            Log::error('🔴 [PayDunya Callback PER] Token manquant dans la payload', [
                'payload_keys' => array_keys($payload),
            ]);
            return response()->json(['error' => 'missing_token'], 422);
        }

        if (empty($status)) {
            Log::error('🔴 [PayDunya Callback PER] Statut manquant dans la payload');
            return response()->json(['error' => 'missing_status'], 422);
        }

        Log::info('🔍 [PayDunya Callback PER] Recherche de la transaction de retrait');

        // Chercher la transaction par disburse_id (description contient le disburse_invoice)
        // ou par le token dans la description
        $withdrawal = WalletTransaction::where('description', 'like', "%{$token}%")
            ->where('type', 'debit')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$withdrawal) {
            Log::error('🔴 [PayDunya Callback PER] Transaction de retrait introuvable', [
                'token' => $token,
                'searched_pattern' => "%{$token}%",
            ]);
            return response()->json(['error' => 'unknown_reference'], 404);
        }

        Log::info('✅ [PayDunya Callback PER] Transaction de retrait trouvée', [
            'withdrawal_id' => $withdrawal->id,
            'user_id' => $withdrawal->user_id,
            'amount' => $withdrawal->amount,
            'current_status' => $withdrawal->status,
        ]);

        // Normaliser le statut
        $normalizedStatus = match ($status) {
            'success', 'completed' => WalletTransaction::STATUS_COMPLETED,
            'failed', 'cancelled' => WalletTransaction::STATUS_REJECTED,
            'pending' => WalletTransaction::STATUS_PENDING,
            default => WalletTransaction::STATUS_PENDING,
        };

        Log::info('🎯 [PayDunya Callback PER] Statut normalisé', [
            'original_status' => $status,
            'normalized_status' => $normalizedStatus,
            'needs_processing' => $normalizedStatus !== $withdrawal->status,
        ]);

        // Si le statut a changé, mettre à jour
        if ($normalizedStatus !== $withdrawal->status) {
            if ($normalizedStatus === WalletTransaction::STATUS_COMPLETED) {
                Log::info('✅ [PayDunya Callback PER] Retrait complété avec succès (wallet déjà débité)');

                try {
                    DB::transaction(function () use ($withdrawal, $payload, $normalizedStatus, $transactionId, $disburseTxId) {
                        Log::info('🔄 [PayDunya Callback PER] Transaction DB démarrée');

                        $wallet = $withdrawal->wallet;
                        if (!$wallet) {
                            Log::error('🔴 [PayDunya Callback PER] Wallet introuvable', [
                                'withdrawal_id' => $withdrawal->id,
                                'wallet_id' => $withdrawal->wallet_id,
                            ]);
                            throw new Exception('Wallet introuvable pour le retrait PayDunya.');
                        }

                        Log::info('💳 [PayDunya Callback PER] Wallet trouvé', [
                            'wallet_id' => $wallet->id,
                            'current_balance' => $wallet->balance,
                        ]);

                        // Wallet déjà débité lors de l'initiation du retrait
                        // On met juste à jour le statut et les métadonnées

                        $withdrawal->status = $normalizedStatus;
                        $withdrawal->description .= " | PayDunya TX: {$transactionId}";

                        if ($disburseTxId) {
                            $withdrawal->description .= " | Provider: {$disburseTxId}";
                        }

                        $withdrawal->save();

                        Log::info('🎉 [PayDunya Callback PER] Retrait complété avec succès', [
                            'withdrawal_id' => $withdrawal->id,
                            'amount' => $withdrawal->amount,
                            'wallet_id' => $wallet->id,
                            'wallet_balance' => $wallet->balance,
                            'transaction_id' => $transactionId,
                            'disburse_tx_id' => $disburseTxId,
                        ]);
                    });

                    Log::info('✅ [PayDunya Callback PER] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========');

                } catch (Exception $exception) {
                    Log::error('💥 [PayDunya Callback PER] Exception lors du traitement', [
                        'token' => $token,
                        'exception_type' => get_class($exception),
                        'exception_message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    return response()->json(['error' => 'processing_failed'], 500);
                }

            } elseif ($normalizedStatus === WalletTransaction::STATUS_REJECTED) {
                Log::warning('⚠️ [PayDunya Callback PER] Retrait échoué ou annulé, recréditation du wallet');

                try {
                    DB::transaction(function () use ($withdrawal, $normalizedStatus, $status) {
                        $wallet = $withdrawal->wallet;

                        if ($wallet) {
                            // Recréditer le wallet car le retrait a échoué
                            $this->paymentService->createPaymentLinkWithExternal(
                                (float)$withdrawal->amount,
                                $wallet,
                                PaymentType::CREDIT
                            );

                            Log::info('✅ [PayDunya Callback PER] Wallet recrédité', [
                                'amount' => $withdrawal->amount,
                                'new_balance' => $wallet->refresh()->balance,
                            ]);
                        }

                        $withdrawal->update([
                            'status' => $normalizedStatus,
                            'description' => $withdrawal->description . " | PayDunya: {$status}",
                        ]);
                    });
                } catch (Exception $exception) {
                    Log::error('💥 [PayDunya Callback PER] Exception lors de la recréditation', [
                        'exception_message' => $exception->getMessage(),
                    ]);
                }

                Log::info('✅ [PayDunya Callback PER] Statut mis à jour à REJECTED');

            } else {
                // Status PENDING ou autre
                Log::info('ℹ️ [PayDunya Callback PER] Mise à jour du statut à PENDING', [
                    'status' => $normalizedStatus,
                ]);

                $withdrawal->update([
                    'status' => $normalizedStatus,
                ]);
            }

            Log::info('✅ [PayDunya Callback PER] ========== CALLBACK TRAITÉ ==========');

        } else {
            Log::info('ℹ️ [PayDunya Callback PER] Statut inchangé, aucune action nécessaire', [
                'current_status' => $withdrawal->status,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function handlePaydunyaPaymentCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('🔔 [PayDunya Callback] ========== CALLBACK REÇU ==========');
        Log::info('🔔 [PayDunya Callback] Payload complet', ['payload' => $payload]);

        // Vérifier le hash pour sécurité (selon la doc PayDunya)
        $receivedHash = $payload['data']['hash'] ?? null;
        $masterKey = config('services.paydunya.master_key');
        $expectedHash = hash('sha512', $masterKey);

        Log::info('🔐 [PayDunya Callback] Vérification du hash', [
            'has_received_hash' => !empty($receivedHash),
            'master_key_configured' => !empty($masterKey),
        ]);

        if ($receivedHash && $receivedHash !== $expectedHash) {
            Log::error('🔴 [PayDunya Callback] Hash invalide - Possible tentative frauduleuse', [
                'received_hash' => substr($receivedHash, 0, 20) . '...',
                'expected_hash' => substr($expectedHash, 0, 20) . '...',
            ]);
            return response()->json(['error' => 'invalid_hash'], 403);
        }

        Log::info('✅ [PayDunya Callback] Hash valide');

        $invoiceData = $payload['data']['invoice'] ?? null;
        $token = $invoiceData['token'] ?? $payload['token'] ?? null;
        $status = strtolower($payload['data']['status'] ?? $payload['status'] ?? '');

        Log::info('📋 [PayDunya Callback] Extraction des données', [
            'token' => $token,
            'status' => $status,
            'has_invoice_data' => !empty($invoiceData),
        ]);

        if (!$token) {
            Log::error('🔴 [PayDunya Callback] Token manquant dans la payload', [
                'payload_keys' => array_keys($payload),
                'data_keys' => isset($payload['data']) ? array_keys($payload['data']) : [],
            ]);
            return response()->json(['error' => 'missing_token'], 422);
        }

        if (empty($status)) {
            Log::error('🔴 [PayDunya Callback] Statut manquant dans la payload');
            return response()->json(['error' => 'missing_status'], 422);
        }

        Log::info('🔍 [PayDunya Callback] Recherche de la demande de paiement', ['token' => $token]);

        /** @var PaydunyaPaymentRequest|null $paymentRequest */
        $paymentRequest = PaydunyaPaymentRequest::where('reference_number', $token)->first();

        if (!$paymentRequest) {
            Log::error('🔴 [PayDunya Callback] Demande de paiement introuvable en base', [
                'token' => $token,
                'searched_in' => 'paydunya_payment_requests.reference_number',
            ]);
            return response()->json(['error' => 'unknown_reference'], 404);
        }

        Log::info('✅ [PayDunya Callback] Demande de paiement trouvée', [
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $paymentRequest->user_id,
            'amount' => $paymentRequest->amount,
            'current_status' => $paymentRequest->status,
        ]);

        $normalizedStatus = match ($status) {
            'completed', 'success' => PaydunyaPaymentRequest::STATUS_COMPLETED,
            'failed', 'cancelled' => PaydunyaPaymentRequest::STATUS_FAILED,
            default => PaydunyaPaymentRequest::STATUS_PENDING,
        };

        Log::info('🎯 [PayDunya Callback] Statut normalisé', [
            'original_status' => $status,
            'normalized_status' => $normalizedStatus,
            'needs_processing' => $normalizedStatus === PaydunyaPaymentRequest::STATUS_COMPLETED && $paymentRequest->status !== PaydunyaPaymentRequest::STATUS_COMPLETED,
        ]);

        if ($normalizedStatus === PaydunyaPaymentRequest::STATUS_COMPLETED && $paymentRequest->status !== PaydunyaPaymentRequest::STATUS_COMPLETED) {
            Log::info('💰 [PayDunya Callback] Début du traitement du paiement complété');

            try {
                DB::transaction(function () use ($paymentRequest, $payload) {
                    Log::info('🔄 [PayDunya Callback] Transaction DB démarrée');

                    $wallet = $paymentRequest->wallet;
                    if (!$wallet) {
                        Log::error('🔴 [PayDunya Callback] Wallet introuvable', [
                            'payment_request_id' => $paymentRequest->id,
                            'wallet_id' => $paymentRequest->wallet_id,
                        ]);
                        throw new Exception('Wallet introuvable pour la demande PayDunya.');
                    }

                    Log::info('💳 [PayDunya Callback] Wallet trouvé', [
                        'wallet_id' => $wallet->id,
                        'current_balance' => $wallet->balance,
                    ]);

                    Log::info('➕ [PayDunya Callback] Crédit du wallet en cours');
                    $result = $this->paymentService->createPaymentLinkWithExternal(
                        (float)$paymentRequest->amount,
                        $wallet,
                        PaymentType::CREDIT
                    );

                    if (!$result) {
                        Log::error('🔴 [PayDunya Callback] Échec du crédit wallet');
                        throw new Exception('Impossible de créditer le wallet via PaymentService.');
                    }

                    Log::info('✅ [PayDunya Callback] Wallet crédité, mise à jour du statut');
                    $paymentRequest->status = PaydunyaPaymentRequest::STATUS_COMPLETED;
                    $paymentRequest->callback_payload = $payload;
                    $paymentRequest->completed_at = now();
                    $paymentRequest->save();

                    Log::info('🎉 [PayDunya Callback] Transaction complétée avec succès', [
                        'token' => $paymentRequest->reference_number,
                        'amount' => $paymentRequest->amount,
                        'wallet_id' => $wallet->id,
                        'new_balance' => $wallet->fresh()->balance,
                    ]);
                });

                Log::info('✅ [PayDunya Callback] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========');
            } catch (Exception $exception) {
                Log::error('💥 [PayDunya Callback] Exception lors du traitement', [
                    'token' => $token,
                    'exception_type' => get_class($exception),
                    'exception_message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);

                return response()->json(['error' => 'credit_failed'], 500);
            }
        } else {
            Log::info('ℹ️ [PayDunya Callback] Mise à jour du statut seulement (pas de crédit)', [
                'new_status' => $normalizedStatus,
                'reason' => $paymentRequest->status === PaydunyaPaymentRequest::STATUS_COMPLETED ? 'Déjà complété' : 'Statut non-complété',
            ]);

            if ($paymentRequest->status !== PaydunyaPaymentRequest::STATUS_COMPLETED) {
                $paymentRequest->update([
                    'status' => $normalizedStatus,
                    'callback_payload' => $payload,
                ]);
                Log::info('✅ [PayDunya Callback] Statut mis à jour');
            }

            Log::info('✅ [PayDunya Callback] ========== CALLBACK TRAITÉ ==========');
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Endpoint PSR (Paiement Sans Redirection) PayDunya
     * Génère un token de paiement pour l'app mobile
     */
    public function getPaydunyaPSRToken(Request $request): JsonResponse
    {
        Log::info('🔵 [PayDunya PSR] Début getPaydunyaPSRToken', [
            'request_data' => $request->all(),
        ]);

        // Validation
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'wallet_id' => 'required|string|exists:wallets,id',
            'amount' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $userId = (int)$validated['user_id'];
            $walletId = $validated['wallet_id'];
            $amount = (float)$validated['amount'];
            $description = $validated['description'] ?? "Recharge de wallet";

            // Vérifier le wallet
            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria($userId));
            $wallet = $this->walletRepository->find($walletId);

            if (!$wallet) {
                Log::error('🔴 [PayDunya PSR] Wallet non trouvé', [
                    'user_id' => $userId,
                    'wallet_id' => $walletId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet non trouvé ou non autorisé',
                ], 404);
            }

            Log::info('✅ [PayDunya PSR] Wallet trouvé', [
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
            ]);

            // Créer l'invoice PayDunya Checkout
            $items = [[
                'name' => 'Recharge de wallet',
                'quantity' => 1,
                'unit_price' => (int)$amount,
                'total_price' => (int)$amount,
                'description' => $description,
            ]];

            $callbackUrl = url('/api/paydunya/payment/callback');

            $options = [
                'description' => $description,
                'callback_url' => $callbackUrl,
                'return_url' => url('/payment/return'),
                'cancel_url' => url('/payment/cancel'),
                'custom_data' => [
                    'user_id' => $userId,
                    'wallet_id' => $wallet->id,
                    'psr' => true,  // Marqueur PSR
                ],
            ];

            Log::info('📤 [PayDunya PSR] Création de l\'invoice PayDunya');

            $response = $this->paydunyaCheckoutService->createInvoice($amount, $items, $options);

            if (!$response['success']) {
                Log::error('🔴 [PayDunya PSR] Échec création invoice', [
                    'message' => $response['message'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $response['message'],
                ], 400);
            }

            $token = $response['data']['token'] ?? null;
            $paymentUrl = $response['data']['payment_url'] ?? null;

            if (!$token) {
                Log::error('🔴 [PayDunya PSR] Token manquant dans la réponse');
                return response()->json([
                    'success' => false,
                    'message' => 'Token PayDunya manquant',
                ], 500);
            }

            // Enregistrer la demande de paiement
            $paymentRequest = PaydunyaPaymentRequest::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'reference_number' => $token,
                'status' => PaydunyaPaymentRequest::STATUS_PENDING,
                'payment_url' => $paymentUrl,
            ]);

            Log::info('🎉 [PayDunya PSR] Token généré avec succès', [
                'token' => $token,
                'payment_request_id' => $paymentRequest->id,
            ]);

            // Retourner la réponse selon la doc PSR
            $mode = $this->paydunyaCheckoutService->getMode();

            $responseData = [
                'success' => true,
                'token' => $token,
            ];

            // Ajouter le mode uniquement en test
            if ($mode === 'test') {
                $responseData['mode'] = 'test';
            }

            return response()->json($responseData);

        } catch (ValidationException $validationException) {
            Log::error('🔴 [PayDunya PSR] Erreur de validation', [
                'errors' => $validationException->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validationException->errors(),
            ], 422);
        } catch (Exception $exception) {
            Log::error('💥 [PayDunya PSR] Exception', [
                'exception_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la génération du token',
            ], 500);
        }
    }

}
