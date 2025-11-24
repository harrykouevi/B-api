<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service PayDunya Checkout (PAR - Paiement Avec Redirection)
 * Basé sur la documentation officielle PayDunya PHP
 * Utilisé pour augmenter le wallet via redirection vers page de paiement PayDunya
 */
class PaydunyaCheckoutService
{
    private ?string $masterKey;
    private ?string $publicKey;
    private ?string $privateKey;
    private ?string $token;
    private string $baseUrl;
    private string $mode; // 'test' ou 'live'

    // Store info
    private ?string $storeName;
    private ?string $storeTagline;
    private ?string $storePhone;
    private ?string $storePostalAddress;
    private ?string $storeWebsiteUrl;
    private ?string $storeLogoUrl;

    // URLs
    private ?string $callbackUrl;
    private ?string $returnUrl;
    private ?string $cancelUrl;

    public function __construct()
    {
        $config = config('services.paydunya.checkout', []);

        $this->masterKey = $config['master_key'] ?? null;
        $this->publicKey = $config['public_key'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->token = $config['token'] ?? null;
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://app.paydunya.com/api/v1', '/');
        $this->mode = $config['mode'] ?? 'live';

        // Store configuration
        $this->storeName = $config['store_name'] ?? config('app.name');
        $this->storeTagline = $config['store_tagline'] ?? null;
        $this->storePhone = $config['store_phone'] ?? null;
        $this->storePostalAddress = $config['store_postal_address'] ?? null;
        $this->storeWebsiteUrl = $config['store_website_url'] ?? config('app.url');
        $this->storeLogoUrl = $config['store_logo_url'] ?? null;

        // URLs
        $this->callbackUrl = $config['callback_url'] ?? null;
        $this->returnUrl = $config['return_url'] ?? null;
        $this->cancelUrl = $config['cancel_url'] ?? null;
    }

    /**
     * Créer une facture de paiement (invoice) selon la doc PAR
     *
     * @param float $amount Montant total à facturer
     * @param array $items Articles de la facture ['name', 'quantity', 'unit_price', 'total_price', 'description']
     * @param array $options Options additionnelles (description, channels, cancel_url, return_url, callback_url)
     * @return array
     */
    public function createInvoice(float $amount, array $items = [], array $options = []): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        $payload = [
            'invoice' => [
                'total_amount' => (int) $amount,
                'description' => $options['description'] ?? "Paiement de {$amount} FCFA",
            ],
            'store' => [
                'name' => $this->storeName,
                'tagline' => $this->storeTagline,
                'phone' => $this->storePhone,
                'postal_address' => $this->storePostalAddress,
                'website_url' => $this->storeWebsiteUrl,
                'logo_url' => $this->storeLogoUrl,
            ],
            'actions' => [
                'cancel_url' => $options['cancel_url'] ?? $this->cancelUrl,
                'return_url' => $options['return_url'] ?? $this->returnUrl,
                'callback_url' => $options['callback_url'] ?? $this->callbackUrl,
            ],
        ];

        // Ajouter les items si fournis
        if (!empty($items)) {
            $payload['invoice']['items'] = $items;
        }

        // Ajouter les taxes si fournies
        if (isset($options['taxes']) && is_array($options['taxes'])) {
            $payload['invoice']['taxes'] = $options['taxes'];
        }

        // Custom data optionnel
        if (isset($options['custom_data']) && is_array($options['custom_data'])) {
            $payload['custom_data'] = $options['custom_data'];
        }

        // Restriction des moyens de paiement si spécifié
        if (isset($options['channels']) && is_array($options['channels'])) {
            $payload['channels'] = $options['channels'];
        }

        $response = $this->post('/checkout-invoice/create', $payload);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $token = $data['token'] ?? null;
        $responseUrl = $data['response_url'] ?? null;

        if (empty($token) || empty($responseUrl)) {
            return [
                'success' => false,
                'message' => 'Réponse PayDunya Checkout invalide : token ou URL manquant.',
                'data' => $data,
            ];
        }

        return [
            'success' => true,
            'message' => $response['message'] ?? 'Facture PayDunya créée avec succès',
            'data' => [
                'token' => $token,
                'invoice_url' => $responseUrl,
                'payment_url' => $responseUrl,
                'raw' => $data,
            ],
        ];
    }

    /**
     * Vérifier le statut d'une facture via son token
     *
     * @param string $token Token de la facture
     * @return array
     */
    public function confirmInvoice(string $token): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token PayDunya manquant.',
            ];
        }

        $response = $this->get("/checkout-invoice/confirm/{$token}");

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $status = $data['status'] ?? null;

        return [
            'success' => true,
            'message' => 'Statut récupéré avec succès',
            'data' => [
                'status' => $status, // 'completed', 'pending', 'cancelled'
                'customer' => $data['customer'] ?? [],
                'receipt_url' => $data['receipt_url'] ?? null,
                'invoice' => $data['invoice'] ?? [],
                'custom_data' => $data['custom_data'] ?? [],
                'raw' => $data,
            ],
        ];
    }

    /**
     * Effectuer une requête POST vers l'API PayDunya
     */
    private function post(string $endpoint, array $payload): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        $url = "{$this->baseUrl}{$endpoint}";

        try {
            Log::info('PayDunya Checkout POST request', [
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($this->buildHeaders())
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $data = $response->json();

            Log::info('PayDunya Checkout POST response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $data,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya Checkout',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya Checkout réussie' : 'Erreur PayDunya Checkout'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur PayDunya Checkout POST', [
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la communication avec PayDunya Checkout.',
            ];
        }
    }

    /**
     * Effectuer une requête GET vers l'API PayDunya
     */
    private function get(string $endpoint): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        $url = "{$this->baseUrl}{$endpoint}";

        try {
            Log::info('PayDunya Checkout GET request', [
                'url' => $url,
            ]);

            $response = Http::withHeaders($this->buildHeaders())
                ->acceptJson()
                ->get($url);

            $data = $response->json();

            Log::info('PayDunya Checkout GET response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $data,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya Checkout',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya Checkout réussie' : 'Erreur PayDunya Checkout'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur PayDunya Checkout GET', [
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la communication avec PayDunya Checkout.',
            ];
        }
    }

    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-PUBLIC-KEY' => $this->publicKey,
            'PAYDUNYA-TOKEN' => $this->token,
            'PAYDUNYA-MODE' => $this->mode,
        ];
    }

    private function hasCredentials(): bool
    {
        return !empty($this->masterKey)
            && !empty($this->privateKey)
            && !empty($this->publicKey)
            && !empty($this->token);
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }
}
