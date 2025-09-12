<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Repositories\UserRepository;
use App\Types\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaygateService
{
    private PaymentService $paymentService;
    private UserRepository $userRepository;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct(PaymentService $paymentService,  UserRepository $userRepository)
    {
        $this->apiKey = config('services.paygate.api_key');
        $this->baseUrl = config('services.paygate.base_url', 'https://paygateglobal.com');
        $this->paymentService = $paymentService;
        $this->userRepository = $userRepository;
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
        float $amount,
        string $identifier,
        string $notifyUrl = null,
        ?string $description = null,
        ?string $phoneNumber = null,
        ?string $network = null,
        ?string $returnUrl = null
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
                'url' => $notifyUrl,

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
            $redirect_url = "$this->baseUrl/v1/page?token=$this->apiKey&amount=1&description=test&identifier=$identifier}";

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
                "identifier" => $transactionId
            ];

            Log::info("Paygate checkPaymentState request", [
                'url' => $url,
                'data' => $data
            ]);

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
    public function handleReturnUrl(Request $request, string $userId): void
    {
        try {
            Log::info("Paygate handleReturnUrl", [
                'request_data' => $request->all(),
                'user_id' => $userId
            ]);

            // Vérifier que tx_reference est présent
            if (!$request->has('tx_reference')) {
                Log::warning("tx_reference manquant dans le retour Paygate", [
                    'request_data' => $request->all()
                ]);
                return;
            }

            $txReference = $request->tx_reference;

            // Vérifier l'état du paiement
            $response = $this->checkPaymentState($txReference);

            if (!$response['success']) {
                Log::error("Échec de la vérification du paiement", [
                    'tx_reference' => $txReference,
                    'error' => $response['message']
                ]);
                return;
            }

            $data = $response['data'];

            // Vérifier que la transaction est réussie
            if (isset($data['status']) && $data['status'] == 0) {
                $amount = $data['amount'] ?? 0;
                $user = $this->userRepository->find($userId);
                if ($amount > 0 && $user) {
                    Log::info("Paiement réussi, création du lien de paiement", [
                        'amount' => $amount,
                        'user_id' => $userId,
                        'tx_reference' => $txReference
                    ]);

                    $this->paymentService->createPaymentLinkWithExternal($amount, $user, \App\Types\PaymentType::CREDIT);
                }
            } else {
                Log::warning("Paiement non réussi", [
                    'tx_reference' => $txReference,
                    'status' => $data['status'] ?? 'inconnu'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement du retour Paygate", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
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
