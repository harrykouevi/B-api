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

    public function __construct()
    {
        $config = config('services.paydunya.disburse', []);
        $this->masterKey = $config['master_key'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->token = $config['token'] ?? null;
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://app.paydunya.com/api/v2', '/');
        $this->defaultCallbackUrl = $config['callback_url'] ?? null;
        $this->defaultWithdrawMode = $config['default_withdraw_mode'] ?? null;
    }

    public function getSupportedWithdrawModes(): array
    {
        return $this->supportedWithdrawModes;
    }

    public function getDefaultCallbackUrl(): ?string
    {
        return $this->defaultCallbackUrl;
    }

    public function getDefaultWithdrawMode(): ?string
    {
        return $this->defaultWithdrawMode;
    }

    public function createInvoice(
        string $accountAlias,
        int $amount,
        string $withdrawMode,
        string $callbackUrl,
        ?string $disburseId = null
    ): array {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya PER manquantes ou invalides.',
            ];
        }

        $payload = [
            'account_alias' => $accountAlias,
            'amount' => $amount,
            'withdraw_mode' => $withdrawMode,
            'callback_url' => $callbackUrl,
        ];

        if (!empty($disburseId)) {
            $payload['disburse_id'] = $disburseId;
        }

        $response = $this->post('/disburse/get-invoice', $payload);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data'];
        $disburseInvoice = $data['disburse_token'] ?? $data['disburse_invoice'] ?? null;

        if (empty($disburseInvoice)) {
            return [
                'success' => false,
                'message' => 'Réponse PayDunya invalide : token manquant.',
                'data' => $data,
            ];
        }

        return [
            'success' => true,
            'message' => $response['message'],
            'data' => [
                'disburse_invoice' => $disburseInvoice,
                'raw' => $data,
            ],
        ];
    }

    public function submitInvoice(string $disburseInvoice, ?string $disburseId = null): array
    {
        $payload = [
            'disburse_invoice' => $disburseInvoice,
        ];

        if (!empty($disburseId)) {
            $payload['disburse_id'] = $disburseId;
        }

        return $this->post('/disburse/submit-invoice', $payload);
    }

    public function checkStatus(string $disburseInvoice): array
    {
        $payload = [
            'disburse_invoice' => $disburseInvoice,
        ];

        return $this->post('/disburse/check-status', $payload);
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
            Log::info('PayDunya PER request', [
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($this->buildHeaders())
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $data = $response->json();

            Log::info('PayDunya PER response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $data,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya réussie' : 'Erreur PayDunya'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur PayDunya PER', [
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
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
