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
        $config = config('services.paydunya', []);

        $this->masterKey = $config['master_key'] ?? null;
        $this->publicKey = $config['public_key'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->token = $config['token'] ?? null;
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://app.paydunya.com/api/v1', '/');
        $this->mode = $config['mode'] ?? 'test';

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
        Log::info('🔵 [PayDunya Checkout] Début createInvoice', [
            'amount' => $amount,
            'items_count' => count($items),
            'has_options' => !empty($options),
        ]);

        if (!$this->hasCredentials()) {
            Log::error('🔴 [PayDunya Checkout] Credentials manquantes', [
                'has_master_key' => !empty($this->masterKey),
                'has_public_key' => !empty($this->publicKey),
                'has_private_key' => !empty($this->privateKey),
                'has_token' => !empty($this->token),
            ]);
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        Log::info('🟢 [PayDunya Checkout] Credentials OK, préparation du payload', [
            'store_name' => $this->storeName,
            'mode' => $this->mode,
        ]);

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
            Log::info('📦 [PayDunya Checkout] Items ajoutés', ['items_count' => count($items)]);
        }

        // Ajouter les taxes si fournies
        if (isset($options['taxes']) && is_array($options['taxes'])) {
            $payload['invoice']['taxes'] = $options['taxes'];
            Log::info('💰 [PayDunya Checkout] Taxes ajoutées', ['taxes_count' => count($options['taxes'])]);
        }

        // Custom data optionnel
        if (isset($options['custom_data']) && is_array($options['custom_data'])) {
            $payload['custom_data'] = $options['custom_data'];
            Log::info('📝 [PayDunya Checkout] Custom data ajoutées', [
                'keys' => array_keys($options['custom_data'])
            ]);
        }

        // Restriction des moyens de paiement si spécifié
        if (isset($options['channels']) && is_array($options['channels'])) {
            $payload['channels'] = $options['channels'];
            Log::info('🎛️ [PayDunya Checkout] Canaux restreints', ['channels' => $options['channels']]);
        }

        Log::info('📤 [PayDunya Checkout] Envoi de la requête à l\'API', [
            'endpoint' => '/checkout-invoice/create',
            'callback_url' => $payload['actions']['callback_url'] ?? 'non défini',
        ]);

        $response = $this->post('/checkout-invoice/create', $payload);

        if (!$response['success']) {
            Log::error('🔴 [PayDunya Checkout] Échec de création de facture', [
                'message' => $response['message'],
                'response' => $response,
            ]);
            return $response;
        }

        Log::info('✅ [PayDunya Checkout] Réponse API reçue avec succès');

        $data = $response['data'] ?? [];
        $token = $data['token'] ?? null;
        // PayDunya retourne l'URL dans le champ 'response_text' au lieu de 'response_url'
        $responseUrl = $data['response_text'] ?? $data['response_url'] ?? null;

        if (empty($token) || empty($responseUrl)) {
            Log::error('🔴 [PayDunya Checkout] Token ou URL manquant dans la réponse', [
                'has_token' => !empty($token),
                'has_response_url' => !empty($responseUrl),
                'data_keys' => array_keys($data),
            ]);
            return [
                'success' => false,
                'message' => 'Réponse PayDunya Checkout invalide : token ou URL manquant.',
                'data' => $data,
            ];
        }

        Log::info('🎉 [PayDunya Checkout] Facture créée avec succès', [
            'token' => $token,
            'payment_url' => $responseUrl,
        ]);

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
        Log::info('🔍 [PayDunya Checkout] Début confirmInvoice', ['token' => $token]);

        if (!$this->hasCredentials()) {
            Log::error('🔴 [PayDunya Checkout] Credentials manquantes pour confirmation');
            return [
                'success' => false,
                'message' => 'Clés PayDunya Checkout manquantes ou invalides.',
            ];
        }

        if (empty($token)) {
            Log::error('🔴 [PayDunya Checkout] Token manquant');
            return [
                'success' => false,
                'message' => 'Token PayDunya manquant.',
            ];
        }

        Log::info('📤 [PayDunya Checkout] Vérification du statut de la facture');
        $response = $this->get("/checkout-invoice/confirm/{$token}");

        if (!$response['success']) {
            Log::error('🔴 [PayDunya Checkout] Échec de confirmation', [
                'message' => $response['message'],
            ]);
            return $response;
        }

        $data = $response['data'] ?? [];
        $status = $data['status'] ?? null;

        Log::info('✅ [PayDunya Checkout] Statut récupéré', [
            'status' => $status,
            'has_customer' => isset($data['customer']),
            'has_receipt_url' => isset($data['receipt_url']),
        ]);

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
            $headers = $this->buildHeaders();

            Log::info('🌐 [PayDunya Checkout] POST Request Details', [
                'url' => $url,
                'endpoint' => $endpoint,
                'payload_keys' => array_keys($payload),
                'has_invoice' => isset($payload['invoice']),
                'has_store' => isset($payload['store']),
                'has_actions' => isset($payload['actions']),
                'headers_present' => array_keys($headers),
            ]);

            Log::debug('📋 [PayDunya Checkout] Payload complet', [
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $statusCode = $response->status();
            $data = $response->json();

            Log::info('📥 [PayDunya Checkout] POST Response Received', [
                'url' => $url,
                'status_code' => $statusCode,
                'success' => $response->successful(),
                'response_code' => $data['response_code'] ?? 'non défini',
            ]);

            Log::debug('📋 [PayDunya Checkout] Response body complet', [
                'body' => $data,
            ]);

            if ($response->failed()) {
                Log::error('❌ [PayDunya Checkout] HTTP Request Failed', [
                    'status_code' => $statusCode,
                    'error_message' => $data['response_text'] ?? $data['description'] ?? 'Erreur inconnue',
                    'full_response' => $data,
                ]);
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya Checkout',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            if ($success) {
                Log::info('✅ [PayDunya Checkout] Request Successful', [
                    'response_code' => $responseCode,
                ]);
            } else {
                Log::warning('⚠️ [PayDunya Checkout] Request Completed but not successful', [
                    'response_code' => $responseCode,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur',
                ]);
            }

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya Checkout réussie' : 'Erreur PayDunya Checkout'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('💥 [PayDunya Checkout] Exception during POST request', [
                'endpoint' => $endpoint,
                'exception_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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
            Log::info('🌐 [PayDunya Checkout] GET Request', [
                'url' => $url,
                'endpoint' => $endpoint,
            ]);

            $response = Http::withHeaders($this->buildHeaders())
                ->acceptJson()
                ->get($url);

            $statusCode = $response->status();
            $data = $response->json();

            Log::info('📥 [PayDunya Checkout] GET Response', [
                'url' => $url,
                'status_code' => $statusCode,
                'success' => $response->successful(),
            ]);

            Log::debug('📋 [PayDunya Checkout] GET Response body', [
                'body' => $data,
            ]);

            if ($response->failed()) {
                Log::error('❌ [PayDunya Checkout] GET Request Failed', [
                    'status_code' => $statusCode,
                    'error_message' => $data['response_text'] ?? $data['description'] ?? 'Erreur inconnue',
                ]);
                return [
                    'success' => false,
                    'message' => $data['response_text'] ?? $data['description'] ?? 'Erreur PayDunya Checkout',
                    'data' => $data,
                ];
            }

            $responseCode = $data['response_code'] ?? null;
            $success = $responseCode === '00';

            Log::info($success ? '✅ [PayDunya Checkout] GET Successful' : '⚠️ [PayDunya Checkout] GET not successful', [
                'response_code' => $responseCode,
            ]);

            return [
                'success' => $success,
                'message' => $data['response_text'] ?? $data['description'] ?? ($success ? 'Opération PayDunya Checkout réussie' : 'Erreur PayDunya Checkout'),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::error('💥 [PayDunya Checkout] Exception during GET request', [
                'endpoint' => $endpoint,
                'exception_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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
