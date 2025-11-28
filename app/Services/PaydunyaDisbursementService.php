<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaydunyaDisbursementService
{
    private ?string $masterKey;
    private ?string $privateKey;
    private ?string $token;
    private string $baseUrl;
    private ?string $defaultCallbackUrl;
    private ?string $defaultWithdrawMode;

    // Modes de retrait pour le Togo
    public const WITHDRAW_MODE_TMONEY = 't-money-togo';
    public const WITHDRAW_MODE_MOOV_TOGO = 'moov-togo';

    // Mapping des types de compte vers les modes de retrait PayDunya
    private array $accountTypeToWithdrawMode = [
        'yas' => self::WITHDRAW_MODE_TMONEY,      // YAS = T-Money (Togocel)
        'moov' => self::WITHDRAW_MODE_MOOV_TOGO,  // Moov = Moov Togo
    ];

    /**
     * @var string[]
     */
    private array $supportedWithdrawModes = [
        'paydunya',
        'orange-money-senegal',
        'free-money-senegal',
        'expresso-senegal',
        'wave-senegal',
        'mtn-benin',
        'moov-benin',
        'mtn-ci',
        'orange-money-ci',
        'moov-ci',
        'wave-ci',
        't-money-togo',
        'moov-togo',
        'orange-money-mali',
        'orange-money-burkina',
        'moov-burkina-faso',
    ];

    // Modes de retrait supportés pour le Togo
    private array $togoWithdrawModes = [
        't-money-togo',
        'moov-togo',
    ];

    public function __construct()
    {
        $config = config('services.paydunya', []);
        $this->masterKey = $config['master_key'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->token = $config['token'] ?? null;
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://app.paydunya.com/api/v2', '/');
        $this->defaultCallbackUrl = $config['callback_url'] ?? null;
        $this->defaultWithdrawMode = $config['default_withdraw_mode'] ?? self::WITHDRAW_MODE_TMONEY;
    }

    public function getSupportedWithdrawModes(): array
    {
        return $this->supportedWithdrawModes;
    }

    public function getTogoWithdrawModes(): array
    {
        return $this->togoWithdrawModes;
    }

    public function getDefaultCallbackUrl(): ?string
    {
        return $this->defaultCallbackUrl;
    }

    public function getDefaultWithdrawMode(): ?string
    {
        return $this->defaultWithdrawMode;
    }

    /**
     * Détermine le mode de retrait basé sur le type de compte
     */
    public function getWithdrawModeFromAccountType(?string $accountType): string
    {
        if ($accountType && isset($this->accountTypeToWithdrawMode[$accountType])) {
            return $this->accountTypeToWithdrawMode[$accountType];
        }
        return $this->defaultWithdrawMode ?? self::WITHDRAW_MODE_TMONEY;
    }

    /**
     * Détermine le mode de retrait basé sur le numéro de téléphone (préfixes togolais)
     * - 90, 91, 92, 93 = T-Money (Togocel)
     * - 96, 97, 98, 99 = Moov Togo
     */
    public function getWithdrawModeFromPhoneNumber(string $phoneNumber): string
    {
        // Nettoyer le numéro
        $cleanNumber = preg_replace('/\D/', '', $phoneNumber);

        // Enlever le préfixe 228 si présent
        if (str_starts_with($cleanNumber, '228')) {
            $cleanNumber = substr($cleanNumber, 3);
        }

        // Vérifier le premier chiffre pour déterminer l'opérateur
        if (strlen($cleanNumber) >= 2) {
            $prefix = substr($cleanNumber, 0, 2);

            // Préfixes Togocel (YAS/T-Money): 90, 91, 92, 93, 70, 71, 72, 73
            if (in_array($prefix, ['90', '91', '92', '93', '70', '71', '72', '73'])) {
                return self::WITHDRAW_MODE_TMONEY;
            }

            // Préfixes Moov Togo: 96, 97, 98, 99, 76, 77, 78, 79
            if (in_array($prefix, ['96', '97', '98', '99', '76', '77', '78', '79'])) {
                return self::WITHDRAW_MODE_MOOV_TOGO;
            }
        }

        // Par défaut, utiliser T-Money
        return $this->defaultWithdrawMode ?? self::WITHDRAW_MODE_TMONEY;
    }

    /**
     * Créer une facture de décaissement (Step 1: Get Invoice)
     *
     * @param string $accountAlias Numéro de téléphone sans code pays (ex: 90123456)
     * @param int $amount Montant en XOF
     * @param string $withdrawMode Mode de retrait (t-money-togo, moov-togo, etc.)
     * @param string $callbackUrl URL de callback pour notification
     * @param string|null $disburseId Référence optionnelle de la transaction
     * @return array
     */
    public function createInvoice(
        string $accountAlias,
        int $amount,
        string $withdrawMode,
        string $callbackUrl,
        ?string $disburseId = null
    ): array {
        Log::info('🔵 [PayDunya PER] Début createInvoice', [
            'account_alias' => $accountAlias,
            'amount' => $amount,
            'withdraw_mode' => $withdrawMode,
            'has_disburse_id' => !empty($disburseId),
        ]);

        if (!$this->hasCredentials()) {
            Log::error('🔴 [PayDunya PER] Credentials manquantes', [
                'has_master_key' => !empty($this->masterKey),
                'has_private_key' => !empty($this->privateKey),
                'has_token' => !empty($this->token),
            ]);
            return [
                'success' => false,
                'message' => 'Clés PayDunya PER manquantes ou invalides.',
            ];
        }

        Log::info('🟢 [PayDunya PER] Credentials OK, préparation du payload');

        $payload = [
            'account_alias' => $accountAlias,
            'amount' => $amount,
            'withdraw_mode' => $withdrawMode,
            'callback_url' => $callbackUrl,
        ];

        if (!empty($disburseId)) {
            $payload['disburse_id'] = $disburseId;
            Log::info('📝 [PayDunya PER] Disburse ID ajouté', ['disburse_id' => $disburseId]);
        }

        Log::info('📤 [PayDunya PER] Création de la facture de décaissement');
        $response = $this->post('/disburse/get-invoice', $payload);

        if (!$response['success']) {
            Log::error('🔴 [PayDunya PER] Échec de création de facture', [
                'message' => $response['message'],
            ]);
            return $response;
        }

        $data = $response['data'];
        $disburseInvoice = $data['disburse_token'] ?? $data['disburse_invoice'] ?? null;

        if (empty($disburseInvoice)) {
            Log::error('🔴 [PayDunya PER] Token de décaissement manquant', [
                'data_keys' => array_keys($data),
            ]);
            return [
                'success' => false,
                'message' => 'Réponse PayDunya invalide : token manquant.',
                'data' => $data,
            ];
        }

        Log::info('🎉 [PayDunya PER] Facture de décaissement créée avec succès', [
            'disburse_invoice' => $disburseInvoice,
        ]);

        return [
            'success' => true,
            'message' => $response['message'],
            'data' => [
                'disburse_invoice' => $disburseInvoice,
                'raw' => $data,
            ],
        ];
    }

    /**
     * Soumettre la facture pour exécution (Step 2: Submit Invoice)
     *
     * @param string $disburseInvoice Token de la facture obtenu à l'étape 1
     * @param string|null $disburseId Référence optionnelle de la transaction
     * @return array
     */
    public function submitInvoice(string $disburseInvoice, ?string $disburseId = null): array
    {
        Log::info('🚀 [PayDunya PER] Début submitInvoice', [
            'disburse_invoice' => $disburseInvoice,
            'has_disburse_id' => !empty($disburseId),
        ]);

        $payload = [
            'disburse_invoice' => $disburseInvoice,
        ];

        if (!empty($disburseId)) {
            $payload['disburse_id'] = $disburseId;
        }

        Log::info('📤 [PayDunya PER] Soumission de la facture pour exécution');
        $response = $this->post('/disburse/submit-invoice', $payload);

        if ($response['success']) {
            $status = $response['data']['status'] ?? 'unknown';
            Log::info('✅ [PayDunya PER] Facture soumise avec succès', [
                'status' => $status,
                'transaction_id' => $response['data']['transaction_id'] ?? null,
            ]);
        } else {
            Log::error('🔴 [PayDunya PER] Échec de soumission de facture', [
                'message' => $response['message'],
            ]);
        }

        return $response;
    }

    /**
     * Vérifier le statut d'un décaissement (Step 3: Check Status)
     *
     * @param string $disburseInvoice Token de la facture
     * @return array
     */
    public function checkStatus(string $disburseInvoice): array
    {
        Log::info('🔍 [PayDunya PER] Début checkStatus', [
            'disburse_invoice' => $disburseInvoice,
        ]);

        $payload = [
            'disburse_invoice' => $disburseInvoice,
        ];

        Log::info('📤 [PayDunya PER] Vérification du statut');
        $response = $this->post('/disburse/check-status', $payload);

        if ($response['success']) {
            $status = $response['data']['status'] ?? 'unknown';
            Log::info('✅ [PayDunya PER] Statut récupéré', [
                'status' => $status,
                'transaction_id' => $response['data']['transaction_id'] ?? null,
            ]);
        } else {
            Log::error('🔴 [PayDunya PER] Échec de vérification du statut');
        }

        return $response;
    }

    private function post(string $endpoint, array $payload): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya PER manquantes ou invalides.',
            ];
        }

        $url = "{$this->baseUrl}{$endpoint}";

        try {
            $headers = $this->buildHeaders();

            Log::info('🌐 [PayDunya PER] POST Request Details', [
                'url' => $url,
                'endpoint' => $endpoint,
                'payload_keys' => array_keys($payload),
                'headers_present' => array_keys($headers),
            ]);

            Log::debug('📋 [PayDunya PER] Payload complet', [
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $statusCode = $response->status();
            $data = $response->json();

            Log::info('📥 [PayDunya PER] POST Response Received', [
                'url' => $url,
                'status_code' => $statusCode,
                'success' => $response->successful(),
                'response_code' => $data['response_code'] ?? 'non défini',
            ]);

            Log::debug('📋 [PayDunya PER] Response body complet', [
                'body' => $data,
            ]);

            if ($response->failed()) {
                Log::error('❌ [PayDunya PER] HTTP Request Failed', [
                    'status_code' => $statusCode,
                    'error_message' => $data['response_text'] ?? $data['description'] ?? 'Erreur inconnue',
                    'full_response' => $data,
                ]);
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            if ($success) {
                Log::info('✅ [PayDunya PER] Request Successful', [
                    'response_code' => $responseCode,
                ]);
            } else {
                Log::warning('⚠️ [PayDunya PER] Request Completed but not successful', [
                    'response_code' => $responseCode,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur',
                ]);
            }

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya réussie' : 'Erreur PayDunya'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('💥 [PayDunya PER] Exception during POST request', [
                'endpoint' => $endpoint,
                'exception_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la communication avec PayDunya.',
            ];
        }
    }

    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-TOKEN' => $this->token,
        ];
    }

    private function hasCredentials(): bool
    {
        return !empty($this->masterKey) && !empty($this->privateKey) && !empty($this->token);
    }
}
