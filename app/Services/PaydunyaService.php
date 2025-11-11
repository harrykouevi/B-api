<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PaydunyaService
{
    private ?string $masterKey;
    private ?string $privateKey;
    private ?string $token;
    private string $baseUrl;
    private int $defaultSupportFees;
    private int $defaultSendNotification;
    private const MIN_AMOUNT = 200;
    private const MAX_AMOUNT = 3000000;

    public function __construct()
    {
        $config = config('services.paydunya', []);
        $this->masterKey = $config['master_key'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->token = $config['token'] ?? null;
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://app.paydunya.com/api/v1', '/');
        $this->defaultSupportFees = (int)($config['support_fees'] ?? 1);
        $this->defaultSendNotification = (int)($config['send_notification'] ?? 1);
    }

    public function createPaymentRequest(float $amount, array $payload = []): array
    {
        try {
            $this->validateAmount($amount);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya manquantes ou invalides.',
            ];
        }

        $recipientEmail = $payload['recipient_email'] ?? null;
        $recipientPhone = $payload['recipient_phone'] ?? null;
    
        if (strpos($recipientPhone, '+228') === 0) {
            $recipientPhone = substr($recipientPhone, 4);
        }
    
        if (empty($recipientEmail) && empty($recipientPhone)) {
            return [
                'success' => false,
                'message' => 'PayDunya requiert au moins un email ou un numéro de téléphone.',
            ];
        }

        $body = array_filter([
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
            'description' => $payload['description'] ?? null,
        ], fn($value) => $value !== null && $value !== '');

        $body['amount'] = (int)$amount;
        $body['support_fees'] = $payload['support_fees'] ?? $this->defaultSupportFees;
        $body['send_notification'] = $payload['send_notification'] ?? $this->defaultSendNotification;

        return $this->postRequest('/dmp-api', $body, true);
    }

    public function checkPaymentStatus(string $referenceNumber): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya manquantes ou invalides.',
            ];
        }

        if (empty($referenceNumber)) {
            return [
                'success' => false,
                'message' => 'Référence PayDunya manquante.',
            ];
        }

        return $this->postRequest('/dmp-api/check-status', [
            'reference_number' => $referenceNumber,
        ]);
    }

    private function postRequest(string $endpoint, array $body, bool $normalizeCreateResponse = false): array
    {
        $url = "{$this->baseUrl}{$endpoint}";
        try {
            Log::info('PayDunya request', [
                'url' => $url,
                'payload' => $body,
            ]);

            $response = Http::withHeaders($this->buildHeaders())
                ->acceptJson()
                ->asJson()
                ->post($url, $body);

            $responseData = $response->json();

            Log::info('PayDunya response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $responseData,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => Arr::get($responseData, 'description') ?? 'Erreur PayDunya',
                    'data' => $responseData,
                ];
            }

            if ($normalizeCreateResponse) {
                return $this->normalizeCreateResponse($responseData);
            }

            $success = Arr::get($responseData, 'response-code') === '00';

            return [
                'success' => $success,
                'message' => Arr::get($responseData, 'description') ?? Arr::get($responseData, 'message'),
                'data' => $responseData,
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur PayDunya', [
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la communication avec PayDunya.',
            ];
        }
    }

    private function normalizeCreateResponse(array $responseData): array
    {
        $successFlag = Arr::get($responseData, 'success');
        $responseCode = Arr::get($responseData, 'response-code');

        if ($successFlag === true || $responseCode === '00') {
            return [
                'success' => true,
                'message' => Arr::get($responseData, 'description') ?? Arr::get($responseData, 'message'),
                'data' => [
                    'payment_url' => Arr::get($responseData, 'url'),
                    'reference_number' => Arr::get($responseData, 'reference_number'),
                    'raw' => $responseData,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => Arr::get($responseData, 'description') ?? Arr::get($responseData, 'message') ?? 'Demande PayDunya échouée.',
            'data' => $responseData,
        ];
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

    private function validateAmount(float $amount): void
    {
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            throw new InvalidArgumentException("Le montant PayDunya doit être compris entre " . self::MIN_AMOUNT . " et " . self::MAX_AMOUNT . ".");
        }
    }
}
