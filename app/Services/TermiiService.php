<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiService
{
    private string $apiUrl;
    private string $apiKey;
    private string $sender;
    private string $channel;
    private string $type;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('services.termii.api_url', 'https://api.ng.termii.com'), '/');
        $this->apiKey = (string) config('services.termii.api_key', '');
        $this->sender = (string) config('services.termii.sender', 'CHARM');
        $this->channel = (string) config('services.termii.channel', 'dnd');
        $this->type = (string) config('services.termii.type', 'plain');
    }

    public function refactorPhoneNumber(string $phone): string
    {
        return str_replace(['(', ')', '-', ' ', '+'], '', $phone);
    }

    public function sendSMS(string $code, string $phone): array
    {
        if (empty($this->apiKey)) {
            Log::warning('TermiiService: API key absente');
            return [
                'status' => false,
                'provider' => 'termii',
                'message' => 'Termii API key is missing',
            ];
        }

        $phone = $this->refactorPhoneNumber($phone);
        $message = "Votre code de verification charm est : {$code}";

        $payload = [
            'api_key' => $this->apiKey,
            'to' => $phone,
            'from' => $this->sender,
            'sms' => $message,
            'type' => $this->type,
            'channel' => $this->channel,
        ];

        $endpoint = $this->apiUrl . '/api/sms/send';

        Log::info('TermiiService SMS - request', [
            'url' => $endpoint,
            'to' => $phone,
            'from' => $this->sender,
            'channel' => $this->channel,
            'type' => $this->type,
        ]);

        $response = Http::timeout(20)
            ->acceptJson()
            ->post($endpoint, $payload);

        $body = $response->json();
        $isOk = $response->successful() && (($body['code'] ?? null) === 'ok');

        Log::info('TermiiService SMS - response', [
            'status' => $response->status(),
            'body' => $body,
        ]);

        return [
            'status' => $isOk,
            'provider' => 'termii',
            'message' => $body['message'] ?? ($isOk ? 'Successfully Sent' : 'SMS sending failed'),
            'http_status' => $response->status(),
            'response' => $body,
        ];
    }
}

