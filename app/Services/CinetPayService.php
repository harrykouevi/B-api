<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->baseUrl = config('services.cinetpay.base_url');
    }

    /**
     * Initier un paiement
     *
     * @param float $amount
     * @param string $currency
     * @param string $transactionId
     * @param ?string $customerEmail
     * @param string $customerPhone
     * @param ?string $callbackUrl
     * @param string $description = "Recharge du portfeuille charm"
     * @param string $channels = "MOBILE_MONEY"
     * @return array
     */
    public function initPayment(float $amount, string $currency, string $transactionId, ?string $customerEmail,string $description, string $customerPhone,string $channels, ?string $callbackUrl = null): array
    {
        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => $callbackUrl,
            'return_url' => $callbackUrl,
            'channels' => $channels,
            'customer_phone_number' => $customerPhone,
        ];

        if($customerEmail != null){
            $data['$customerEmail'] = $customerEmail;
        };
        $response = Http::post("{$this->baseUrl}/v2/payment", $data);

        return $response->json();
    }

    /**
     * Vérifier le statut d’un paiement
     *
     * @param string $transactionId
     * @return array
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $response = Http::get("{$this->baseUrl}/transaction/check", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ]);

        return $response->json();
    }
}
