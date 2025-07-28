<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PHPUnit\Exception;

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->baseUrl = config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com');
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
                // Vous pouvez logger l'erreur ici si besoin
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
}