<?php

namespace App\Services;

use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $baseUrl;
    protected string $transferBaseUrl;
    protected string $apiPassword;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->apiPassword = config('services.cinetpay.api_password');
        $this->baseUrl = config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com');
        $this->transferBaseUrl = config('services.cinetpay.transfert_base_url');
    }

    /**
     * Initier un paiement via CinetPay
     *
     * @param float $amount Montant (doit être un entier multiple de 5)
     * @param string $currency Devise (ex: 'XOF')
     * @param string $transactionId Identifiant unique de la transaction
     * @param string $description Description du paiement
     * @param string $channels Canaux de paiement (ex: 'MOBILE_MONEY', 'CREDIT_CARD', 'ALL')
     * @param array $customerData Données client obligatoires pour CB (voir doc)
     * @param string|null $notifyUrl URL de notification (callback)
     * @param string|null $returnUrl URL de retour après paiement
     * @return array Réponse JSON de l'API CinetPay
     * @throws InvalidArgumentException si montant invalide ou données client manquantes
     */
    public function initPayment(
        float   $amount,
        string  $currency,
        string  $transactionId,
        string  $description,
        string  $channels,
        array   $customerData = [],
        ?string $notifyUrl = null,
        ?string $returnUrl = null
    ): array
    {
        try {
            if ((int)$amount != $amount || $amount % 5 !== 0) {
                throw new InvalidArgumentException("Le montant doit être un entier multiple de 5.");
            }

            $data = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $transactionId,
                'amount' => (int)$amount,
                'currency' => $currency,
                'description' => $description,
                'channels' => $channels,
            ];
            Log::info("data", ['data' => $data]);

            if ($notifyUrl !== null) {
                $data['notify_url'] = $notifyUrl;
            }
            if ($returnUrl !== null) {
                $data['return_url'] = $returnUrl;
            }
            if (in_array($channels, ['CREDIT_CARD', 'ALL'])) {
                $requiredCustomerFields = [
                    'customer_id',
                    'customer_name',
                    'customer_surname',
                    'customer_phone_number',
                    'customer_email',
                    'customer_address',
                    'customer_city',
                    'customer_country',
                    'customer_state',
                    'customer_zip_code',
                ];

                foreach ($requiredCustomerFields as $field) {
                    if (empty($customerData[$field])) {
                        throw new InvalidArgumentException("Le champ client obligatoire '$field' est manquant.");
                    }
                    $data[$field] = $customerData[$field];
                }
            } else {
                if (empty($customerData['customer_phone_number'])) {
                    throw new InvalidArgumentException("Le champ 'customer_phone_number' est obligatoire.");
                }
                $data['customer_phone_number'] = $customerData['customer_phone_number'];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v2/payment", $data);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec le service de paiement.',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            return $response->json();

        } catch (InvalidArgumentException $e) {
            Log::info("Erreur", ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::info("Erreur", ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Une erreur inattendue est survenue : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtenir le token d'authentification CinetPay
     *
     * @return string|array
     */
    public function getAuthToken()
    {
        try {
            $url = "{$this->transferBaseUrl}/v1/auth/login?lang=fr&apikey={$this->apiKey}&password={$this->apiPassword}";

            Log::info('CinetPay login request', [
                'url' => $url
            ]);

            $response = Http::get($url);

            Log::info('CinetPay login response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec le service de paiement.',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] !== 0) {
                $errorMessage = $data['message'] ?? 'Erreur d\'authentification inconnue';

                if ($data['code'] == 701) { // Utilisez == pour la comparaison
                    return [
                        'success' => false,
                        'message' => 'Erreur: les identifiants CinetPay sont incorrects',
                    ];
                }

                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];
            }

            return [
                'success' => true,
                'token' => $data['data']['token'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'authentification CinetPay', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'authentification: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Vérifier le solde disponible
     *
     * @param string $token
     * @return array
     */
    public function getTransferBalance(string $token): array
    {
        try {
            $response = Http::get("{$this->transferBaseUrl}/v1/transfer/check/balance", [
                'token' => $token,
                'lang' => 'fr'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la récupération du solde'
                ];
            }

            $data = $response->json();

            // Gérer le token invalide
            if (isset($data['code']) && $data['code'] === 706) {
                Log::warning('Token CinetPay invalide');
                return [
                    'success' => false,
                    'message' => 'Token CinetPay invalide',
                    'code' => 706
                ];
            }

            if (isset($data['code']) && $data['code'] !== 0) {
                $errorMessage = $data['message'] ?? 'Erreur inconnue';
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }

            return [
                'success' => true,
                'total_amount' => (float)($data['data']['amount'] ?? 0),
                'in_using' => (float)($data['data']['inUsing'] ?? 0),
                'available' => (float)($data['data']['available'] ?? 0)
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du solde CinetPay', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du solde: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le solde disponible et autoriser le retrait
     *
     * @param float $withdrawalAmount Montant demandé pour le retrait
     * @return array
     */
    public function checkBalanceAndAuthorizeWithdrawal(float $withdrawalAmount): array
    {
        try {
            // 1. Obtenir le token d'authentification
            $tokenResult = $this->getAuthToken();

            if (is_array($tokenResult) && !$tokenResult['success']) {
                return $tokenResult;
            }

            $token = $tokenResult['token'] ?? $tokenResult;

            // 2. Vérifier le solde disponible
            $balanceData = $this->getTransferBalance($token);

            if (isset($balanceData['success']) && !$balanceData['success']) {
                return $balanceData;
            }

            // 3. Comparer avec le montant demandé
            $availableBalance = $balanceData['available'] ?? 0;
            $canWithdraw = $availableBalance >= $withdrawalAmount;

            return [
                'success' => true,
                'authorized' => $canWithdraw,
                'balance_info' => $balanceData,
                'requested_amount' => $withdrawalAmount,
                'token' => $token,
                'message' => $canWithdraw
                    ? 'Retrait autorisé'
                    : 'Solde insuffisant pour ce retrait'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du solde CinetPay', [
                'error' => $e->getMessage(),
                'withdrawal_amount' => $withdrawalAmount
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du solde: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ajouter un contact pour le transfert
     *
     * @param string $prefix
     * @param string $phone
     * @param string $name
     * @param string $surname
     * @param string $email
     * @return array
     */

public function addContact(string $prefix, string $phone, string $name, string $surname, string $email): array
{
    $tokenResult = $this->getAuthToken();
    $token = is_array($tokenResult) ? $tokenResult['token'] ?? null : $tokenResult;

    // Nettoyage du numéro (garde uniquement les chiffres après le préfixe)
    $rawNumber = preg_replace('/\D/', '', $phone);
    if (strpos($rawNumber, $prefix) === 0) {
        $rawNumber = substr($rawNumber, strlen($prefix));
    }

    Log::info("Token", [
        'token' => $token,
        'prefix' => $prefix,
        'phone' => $rawNumber,
        'name' => $name,
        'surname' => $surname,
        'email' => $email,
        'transfert_base_url' => $this->transferBaseUrl
    ]);

    try {
        // Tableau d'objets comme demandé par la doc
        $contactData = [
            [
            'prefix' => $prefix,
            'phone' => $rawNumber,
            'name' => $name,
            'surname' => $surname,
            'email' => $email
            ]
        ];

        $payload = [
            'data' => json_encode($contactData)
        ];

        // Requête POST en x-www-form-urlencoded
        $url = "{$this->transferBaseUrl}/v1/transfer/contact?token={$token}&lang=fr";
        $response = Http::asForm()->post($url, $payload);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'ajout du contact",
                'response' => $response->body()
            ];
        }

        $responseData = $response->json();

        if (isset($responseData['code']) && $responseData['code'] !== 0) {
            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Erreur lors de l\'ajout du contact',
                'response' => $responseData
            ];
        }

        return [
            'success' => true,
            'lot' => $responseData['data'][0]['lot'] ?? null,
            'response' => $responseData
        ];

    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'ajout du contact CinetPay', [
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'message' => 'Erreur lors de l\'ajout du contact: ' . $e->getMessage()
        ];
    }
}
    /**
     * Exécuter un transfert via CinetPay
     *
     * @param WalletTransaction $withdrawal Transaction de retrait
     * @param string $phoneNumber Numéro de téléphone destinataire
     * @param string $countryPrefix Préfixe pays
     * @param string|null $paymentMethod Méthode de paiement optionnelle
     * @return array
     */
   public function executeTransfer(WalletTransaction $withdrawal, string $phoneNumber, string $countryPrefix, ?string $paymentMethod = null): array
{
    try {
        // 1. Obtenir le token d'authentification
        $tokenResult = $this->getAuthToken();

        if (is_array($tokenResult) && !$tokenResult['success']) {
            return $tokenResult;
        }

        $token = is_array($tokenResult) ? $tokenResult['token'] : $tokenResult;

        // ⚠️ Vérifier que le montant est bien un multiple de 5
        if ($withdrawal->amount % 5 !== 0) {
            return [
                'success' => false,
                'message' => "Le montant doit être un multiple de 5"
            ];
        }

        // 2. Préparer les données de transfert
        $transferData = [[
            'prefix' => $countryPrefix,
            'phone' => $phoneNumber,
            'amount' => $withdrawal->amount,
            'client_transaction_id' => "WD_{$withdrawal->id}_" . time(),
            'notify_url' => route('cinetpay.transfer.webhook', [], false)
        ]];

        // Ajouter la méthode de paiement si spécifiée
        if ($paymentMethod) {
            $transferData[0]['payment_method'] = $paymentMethod;
        }

        Log::info("Payload transfert", $transferData);

        // 3. Exécuter le transfert
        $response = Http::asForm()->post("{$this->transferBaseUrl}/v1/transfer/money/send/contact", [
            'token' => $token,
            'lang' => 'fr',
            'data' => json_encode($transferData) // 🔑 doit être JSON stringifié
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'initiation du transfert",
                'response' => $response->body()
            ];
        }

        $responseData = $response->json();

        // Vérifier si la réponse contient des erreurs
        if (isset($responseData['code']) && $responseData['code'] !== 0) {
            if ($responseData['code'] === 723) {
                return [
                    'success' => false,
                    'message' => 'Le contact n\'existe pas dans CinetPay.',
                    'code' => 723,
                    'response' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Erreur lors du transfert',
                'response' => $responseData
            ];
        }

        // Retourner les données du transfert
        $transferResult = $responseData['data'][0] ?? [];

        return [
            'success' => true,
            'transaction_id' => $transferResult['transaction_id'] ?? null,
            'client_transaction_id' => $transferData[0]['client_transaction_id'],
            'treatment_status' => $transferResult['treatment_status'] ?? null,
            'sending_status' => $transferResult['sending_status'] ?? null,
            'transfer_valid' => $transferResult['transfer_valid'] ?? null,
            'lot' => $transferResult['lot'] ?? null,
            'response' => $transferResult
        ];

    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'exécution du transfert CinetPay', [
            'error' => $e->getMessage(),
            'withdrawal_id' => $withdrawal->id
        ]);

        return [
            'success' => false,
            'message' => 'Erreur lors de l\'exécution du transfert: ' . $e->getMessage()
        ];
    }
}


    /**
     * Vérifier le statut d'un transfert
     *
     * @param string $token
     * @param string $clientTransactionId
     * @return array
     */
    public function checkTransferStatus(string $token, string $clientTransactionId): array
    {
        try {
            $response = Http::get("{$this->transferBaseUrl}/v1/transfer/check/money", [
                'token' => $token,
                'client_transaction_id' => $clientTransactionId,
                'lang' => 'fr'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => "Erreur lors de la vérification du statut",
                    'response' => $response->body()
                ];
            }

            $responseData = $response->json();

            if (isset($responseData['code']) && $responseData['code'] !== 0) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Erreur lors de la vérification du statut',
                    'response' => $responseData
                ];
            }

            return [
                'success' => true,
                'data' => $responseData['data'][0] ?? null,
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut CinetPay', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut: ' . $e->getMessage()
            ];
        }
    }

    public function extractWalletTransactionId(string $clientTransactionId): ?int
    {
        if (preg_match('/^WD_(\d+)_(\d+)$/', $clientTransactionId, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

}
