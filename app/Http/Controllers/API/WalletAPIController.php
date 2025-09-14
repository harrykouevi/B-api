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
use App\Repositories\CurrencyRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\WalletRepository;
use App\Services\CinetPayService;
use App\Services\PaygateService;
use App\Services\PaymentService;
use App\Types\WalletType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
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
    private PaygateService $paygateService;
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(CinetPayService $cinetPayService, PaymentService $paymentService, PaygateService $paygateService, WalletRepository $walletRepo, CurrencyRepository $currencyRepository, PaymentMethodRepository $paymentMethodRepository)
    {
        parent::__construct();
        $this->walletRepository = $walletRepo;
        $this->currencyRepository = $currencyRepository;
        $this->paymentService = $paymentService;
        $this->cinetPayService = $cinetPayService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paygateService = $paygateService;

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
            log::info("notify Url", ['url' => $notifyUrl]);
            $returnUrl = route('payments.return', ['transaction' => $transactionId]);

            // Vérifier si CinetPay est disponible
            $cinetPayTokenResponse = $this->cinetPayService->getAuthToken();

            // Si CinetPay est disponible, utiliser CinetPay
            if (isset($cinetPayTokenResponse['success']) && $cinetPayTokenResponse['success']) {
                log::info("Début d'envoi via CinetPay");
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
                log::info("reponse CinetPay", ['reponse' => $response]);

                if (isset($response['data']['payment_url'])) {
                    return $this->sendResponse($response, "Contact établi avec succès");
                }
            } else {
                // CinetPay n'est pas disponible, utiliser Paygate comme solution de secours
                Log::warning('CinetPay indisponible, bascule vers Paygate', [
                    'cinetpay_error' => $cinetPayTokenResponse['message'] ?? 'Erreur inconnue'
                ]);

                // Initialiser le service Paygate


                log::info("Début d'envoi via Paygate");
                $withdrawal = WalletTransaction::createWithdrawal([
                    'wallet_id' => $wallet->id,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'description' => $description,
                    'status' => WalletTransaction::STATUS_PENDING
                ]);
                Log::info('Transaction de crédit créée', ['withdrawal_id' => $withdrawal->id]);

                $response = $this->paygateService->initPayment(
                    $amount,
                    $withdrawal->id,
                    $returnUrl = route('payments.return', ['transaction' => $withdrawal->id])

                );
                log::info("reponse Paygate", ['reponse' => $response]);

                if (isset($response['data']['payment_url']) || (isset($response['success']) && $response['success'])) {
                    return $this->sendResponse($response, "Recharge effectuée avec succès via Paygate");
                }

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
     * @throws RepositoryException
     * @throws \PHPUnit\Exception
     */
    /**
     * @throws RepositoryException
     * @throws \PHPUnit\Exception
     */
    public function withdrawOnWallet(Request $request): JsonResponse
    {
        // Validation des données
        Log::info('Début du retrait - Validation des données', ['request_data' => $request->all()]);
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:500',
            'description' => 'nullable|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'wallet_id' => 'required|string|exists:wallets,id',
            'phone_number' => 'required|string',
            'country_prefix' => 'required|string',
            'payment_method' => 'nullable|string|in:WAVECI,WAVESN', // Selon le pays
        ]);
        Log::info('Validation des données réussie', ['validated_data' => $validatedData]);

        try {
            $userId = $validatedData['user_id'];
            $walletId = $validatedData['wallet_id'];
            $amount = (float)$validatedData['amount'];
            $phoneNumber = $validatedData['phone_number'];
            $countryPrefix = $validatedData['country_prefix'];
            $paymentMethod = $validatedData['payment_method'] ?? null;

            Log::info('Paramètres extraits', [
                'user_id' => $userId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'country_prefix' => $countryPrefix,
                'payment_method' => $paymentMethod
            ]);

            // Vérifier que le wallet appartient à l'utilisateur et est actif
            Log::info('Vérification du wallet utilisateur', ['user_id' => $userId, 'wallet_id' => $walletId]);
            $this->walletRepository->pushCriteria(new EnabledCriteria());
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria($userId));
            $wallet = $this->walletRepository->find($walletId);

            if (!$wallet) {
                Log::warning('Wallet non trouvé ou non autorisé', ['user_id' => $userId, 'wallet_id' => $walletId]);
                return response()->json([
                    'error' => 'Wallet non trouvé ou non autorisé'
                ], 404);
            }
            Log::info('Wallet trouvé avec succès', ['wallet' => $wallet]);

            // Vérifier que le montant est valide et que le solde est suffisant
            Log::info('Vérification du solde du wallet', ['wallet_balance' => $wallet->balance, 'requested_amount' => $amount]);
            if (!WalletTransaction::canWithdraw($wallet, $amount)) {
                Log::warning('Montant invalide ou solde insuffisant', [
                    'wallet_balance' => $wallet->balance,
                    'requested_amount' => $amount,
                    'can_withdraw' => WalletTransaction::canWithdraw($wallet, $amount)
                ]);
                return response()->json([
                    'error' => 'Montant invalide ou solde insuffisant',
                    'message' => 'Le montant doit être un multiple de 5 et supérieur ou égal à 500. Vérifiez également que votre solde est suffisant.'
                ], 400);
            }
            Log::info('Solde suffisant pour le retrait');

            // Créer la transaction de retrait (statut initial: pending)
            $operatorLabel = $paymentMethod ? " ({$paymentMethod})" : '';
            $description = ($validatedData['description'] ?? 'Demande de retrait') . $operatorLabel;
            Log::info('Création de la transaction de retrait', [
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'amount' => $amount,
                'description' => $description
            ]);

            $withdrawal = WalletTransaction::createWithdrawal([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'amount' => $amount,
                'description' => $description,
                'status' => WalletTransaction::STATUS_PENDING
            ]);
            Log::info('Transaction de retrait créée', ['withdrawal_id' => $withdrawal->id]);

            // Vérifier le solde CinetPay
            Log::info('Vérification du solde CinetPay', ['amount' => $amount]);
            $balanceResponse = $this->cinetPayService->checkBalanceAndAuthorizeWithdrawal($amount);
            Log::info('Réponse de vérification du solde CinetPay', ['response' => $balanceResponse]);

            if (!$balanceResponse['success']) {
                // Marquer la transaction comme rejetée
                Log::warning('Erreur lors de la vérification du solde CinetPay', ['response' => $balanceResponse]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
                Log::info('Statut de la transaction mis à jour à REJECTED', ['withdrawal_id' => $withdrawal->id]);

                return response()->json([
                    'error' => 'Erreur lors de la vérification du solde CinetPay',
                    'message' => $balanceResponse['message'] ?? 'Erreur inconnue'
                ], 500);
            }

            if (!$balanceResponse['authorized']) {
                // Marquer la transaction comme rejetée
                Log::warning('Solde CinetPay insuffisant', ['response' => $balanceResponse]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
                Log::info('Statut de la transaction mis à jour à REJECTED', ['withdrawal_id' => $withdrawal->id]);

                return response()->json([
                    'error' => 'Solde CinetPay insuffisant',
                    'message' => $balanceResponse['message'],
                    'balance_info' => $balanceResponse['balance_info']
                ], 400);
            }
            Log::info('Solde CinetPay suffisant pour le retrait');

            // Exécuter le transfert via CinetPay (le contact doit déjà exister)
            Log::info('Exécution du transfert via CinetPay', [
                'withdrawal_id' => $withdrawal->id,
                'phone_number' => $phoneNumber,
                'country_prefix' => $countryPrefix,
                'payment_method' => $paymentMethod
            ]);
            $transferResponse = $this->cinetPayService->executeTransfer(
                $withdrawal,
                $phoneNumber,
                $countryPrefix,
                $paymentMethod
            );
            Log::info('Réponse de l\'exécution du transfert', ['response' => $transferResponse]);

            if (!$transferResponse['success']) {
                // Marquer la transaction comme rejetée
                Log::warning('Erreur lors de l\'exécution du transfert', ['response' => $transferResponse]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
                Log::info('Statut de la transaction mis à jour à REJECTED', ['withdrawal_id' => $withdrawal->id]);

                // Gérer spécifiquement le cas où le contact n'existe pas
                if (isset($transferResponse['code']) && $transferResponse['code'] === 723) {
                    Log::warning('Contact non trouvé dans CinetPay', ['code' => $transferResponse['code']]);
                    return response()->json([
                        'error' => 'Contact non trouvé',
                        'message' => 'Le contact n\'existe pas dans CinetPay. Veuillez contacter le service support.'
                    ], 400);
                }

                return response()->json([
                    'error' => 'Erreur lors de l\'exécution du transfert',
                    'message' => $transferResponse['message'] ?? 'Erreur inconnue'
                ], 500);
            }
            Log::info('Transfert CinetPay exécuté avec succès');

            // Mettre à jour la transaction avec les informations de CinetPay
            Log::info('Mise à jour de la transaction avec les informations CinetPay', [
                'withdrawal_id' => $withdrawal->id,
                'payment_id' => $transferResponse['client_transaction_id'],
                'transaction_id' => $transferResponse['transaction_id']
            ]);
            $withdrawal->update([
                'payment_id' => $transferResponse['client_transaction_id'],
                'description' => $withdrawal->description . ' (ID: ' . $transferResponse['transaction_id'] . ')'
            ]);
            Log::info('Transaction mise à jour avec succès', ['withdrawal_id' => $withdrawal->id]);

            // Débiter le wallet
            Log::info('Débit du wallet', [
                'wallet_id' => $wallet->id,
                'old_balance' => $wallet->balance,
                'amount' => $amount,
                'new_balance' => $wallet->balance - $amount
            ]);
            $wallet->update([
                'balance' => $wallet->balance - $amount
            ]);
            Log::info('Wallet débité avec succès', ['wallet_id' => $wallet->id, 'new_balance' => $wallet->balance - $amount]);

            // Log de l'opération
            Log::info('Retrait initié avec succès', [
                'user_id' => $userId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'transaction_id' => $transferResponse['transaction_id'],
                'client_transaction_id' => $transferResponse['client_transaction_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Retrait initié avec succès.',
                'transaction_id' => $transferResponse['transaction_id'],
                'client_transaction_id' => $transferResponse['client_transaction_id'],
                'treatment_status' => $transferResponse['treatment_status'],
                'sending_status' => $transferResponse['sending_status'],
                'withdrawal' => $withdrawal
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors du retrait', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user_id ?? null,
                'wallet_id' => $request->wallet_id ?? null
            ]);

            // Si une transaction a été créée, la marquer comme rejetée
            if (isset($withdrawal) && $withdrawal instanceof WalletTransaction) {
                Log::info('Marquage de la transaction comme rejetée suite à une exception', ['withdrawal_id' => $withdrawal->id]);
                $withdrawal->update(['status' => WalletTransaction::STATUS_REJECTED]);
            }

            return response()->json([
                'error' => 'Erreur lors du traitement du retrait',
                'message' => $e->getMessage()
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


}
