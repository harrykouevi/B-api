<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Repositories\UserRepository;
use App\Repositories\WalletTransactionRepository;
use App\Types\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaygateService
{
    private PaymentService $paymentService;

    private UserRepository $userRepository;
    private WalletTransactionRepository $transactionRepository;
    protected ?string $apiKey;
    protected string $baseUrl;


    public function __construct(PaymentService $paymentService, UserRepository $userRepository, WalletTransactionRepository $transactionRepository)
    {
        $this->apiKey = config('services.paygate.api_key');
        $this->baseUrl = config('services.paygate.base_url', 'https://paygateglobal.com');
        $this->paymentService = $paymentService;
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Initier un paiement via Paygate
     *
     * @param float $amount Montant (doit être un entier multiple de 5)
     * @param string $identifier Identifiant unique de la transaction
     * @param string|null $description Description de la transaction
     * @param string|null $phoneNumber Numéro de téléphone du client
     * @param string|null $network Réseau (FLOOZ, TMONEY)
     * @param string|null $notifyUrl URL de notification (callback)
     * @param string|null $returnUrl URL de retour après paiement
     * @return array Réponse JSON de l'API Paygate
     * @throws InvalidArgumentException si montant invalide
     */
    public function initPayment(
        float   $amount,
        string  $identifier,
        string  $returnUrl = null,
        ?string $description = null,
        ?string $phoneNumber = null,
        ?string $network = null,
        ?string $notifyUrl = null
    ): array
    {
        try {
            if ((int)$amount != $amount || $amount % 5 !== 0) {
                throw new InvalidArgumentException("Le montant doit être un entier multiple de 5.");
            }

            $data = [
                'token' => $this->apiKey,
                'amount' => (int)$amount,
                'identifier' => $identifier,
                'url' => $returnUrl,

            ];

            if ($description !== null) {
                $data['description'] = $description;
            }

            if ($phoneNumber !== null) {
                $data['phone'] = $phoneNumber;
            }

            if ($network !== null) {
                $data['network'] = $network;
            }

            if ($returnUrl !== null) {
                $data['url'] = $returnUrl;
            }

            Log::info("Paygate initPayment request", ['data' => $data]);

            $response = Http::get("{$this->baseUrl}/v1/page", $data);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec le service de paiement.',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            $responseData = $response->json();

            // Vérifier si la réponse contient une erreur
            if (isset($responseData['status']) && $responseData['status'] != 0) {
                $errorMessages = [
                    2 => 'Jeton d\'authentification invalide',
                    4 => 'Paramètres invalides',
                    6 => 'Doublons détectés. Une transaction avec le même identifiant existe déjà.',
                ];

                return [
                    'success' => false,
                    'message' => $errorMessages[$responseData['status']] ?? 'Erreur inconnue',
                    'status' => $responseData['status'],
                ];
            }
            $redirect_url = "$this->baseUrl/v1/page?token=$this->apiKey&amount=$amount&description=recharger votre portefeuille sur l'application CHARM&identifier=$identifier}";

            return [
                'success' => true,
                'data' => [
                    'payment_url' => $redirect_url,
                    'data' => $responseData,
                ],
            ];

        } catch (InvalidArgumentException $e) {
            Log::error("Erreur de validation Paygate", ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error("Erreur inattendue Paygate", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Une erreur inattendue est survenue : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier l'état d'un paiement
     *
     * @param string $transactionId Identifiant de la transaction Paygate
     * @return array
     */
    public function checkPaymentState(string $transactionId): array
    {
        try {
            $url = "{$this->baseUrl}/api/v1/status";
            $data = [
                "auth_token" => $this->apiKey,
                "tx_reference" => $transactionId
            ];

            Log::info("Paygate checkPaymentState request", [
                'url' => $url,
                'data' => $data
            ]);

            $response = Http::asJson()->post($url, $data);
            Log::info("Réponse", [
                'url' => $url,
                'data' => $response
            ]);
            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec le service de paiement.',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            $responseData = $response->json();
            Log::info("reponse retournée", [
                'data' => $responseData
            ]);
            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification du paiement", [
                'exception' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gérer le retour après paiement
     *
     * @param Request $request
     * @param string $userId
     * @return void
     */
    public function handleReturnUrl(Request $request): void
    {
        try {
            $transaction = null;
            Log::info("Paygate handleReturnUrl", [
                'request_data' => $request->all(),
            ]);

            // Vérifications des paramètres requis
            if (!$request->has(['tx_reference', 'identifier'])) {
                Log::warning("Paramètres manquants dans le retour Paygate", [
                    'request_data' => $request->all()
                ]);
                return;
            }

            $txReference = $request->tx_reference;
            $identifier = trim($request->identifier, '{}');

            // Récupérer la transaction
            $transaction = $this->transactionRepository->findWithoutFail($identifier);
            if (!$transaction) {
                Log::warning("Transaction introuvable - identifier mismatch", [
                    'identifier_recu' => $identifier,
                    'tx_reference' => $txReference,
                    'callback_data' => $request->all()
                ]);
                return;
            }

            // Vérifier l'état du paiement
            $response = $this->checkPaymentState($txReference);

            if (!$response['success']) {
                Log::error("Échec de la vérification du paiement", [
                    'tx_reference' => $txReference,
                    'error' => $response['message']
                ]);
                $transaction->status = WalletTransaction::STATUS_REJECTED;
                $transaction->save();
                return;
            }

            $data = $response['data'];
            $user = $this->userRepository->find($transaction->user_id);

            if (!$user) {
                Log::error("Utilisateur introuvable", ['user_id' => $transaction->user_id]);
                return;
            }

            // ✅ CORRECTION : Vérifier le bon champ 'status'
            if (isset($data['status']) && $data['status'] == 0) {
                $amount = $request->amount ?? 0 ;
                    $transaction->status = WalletTransaction::STATUS_COMPLETED;

                if ($amount > 0) {
                    Log::info("Paiement réussi, création du lien de paiement", [
                        'amount' => $amount,
                        'user_id' => $user->id,
                        'tx_reference' => $txReference
                    ]);

                    $this->paymentService->createPaymentLinkWithExternal($amount, $user, \App\Types\PaymentType::CREDIT);
                }
            } else {
                Log::warning("Paiement non réussi", [
                    'tx_reference' => $txReference,
                    'status' => $data['status'] ?? 'inconnu'
                ]);
                $transaction->status = WalletTransaction::STATUS_REJECTED;
            }

            $transaction->save();

        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement du retour Paygate", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($transaction) {
                $transaction->status = WalletTransaction::STATUS_REJECTED;
                $transaction->save();
            }
        }
    }

    /**
     * Consulter le solde
     *
     * @return array
     */
    public function checkBalance(): array
    {
        try {
            $url = "{$this->baseUrl}/api/v1/check-balance";
            $data = [
                "auth_token" => $this->apiKey
            ];

            $response = Http::asJson()->post($url, $data);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec le service de paiement.',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            $responseData = $response->json();

            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification du solde", [
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du solde: ' . $e->getMessage(),
            ];
        }
    }
}
