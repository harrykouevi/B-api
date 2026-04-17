<?php
namespace App\Services;

use  Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfoBipService{
    private $api_key;
    private $sender;
    private $api_url;

    private $headers;
    private $body;
    public function __construct(){
        $this->api_url = rtrim(config('services.infobip.api_url'), '/');
        $this->api_key = config('services.infobip.api_key');
        $this->sender = config('services.infobip.sender');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'App '.$this->api_key,
        ];
    }

    public function refactorPhoneNumber(string $phone): string
    { 
        return str_replace(['(',')','-',' ','+'], '', $phone);
    }

 

    public function sendSms(string $code, string $phone): array
    {
        Log::info('Envoi du message SMS au '.$phone);
        $phone = $this->refactorPhoneNumber($phone);

        // Structure conforme à la documentation InfoBip
        $body = [
            'messages' => [
                [
                    'from' => $this->sender,
                    'destinations' => [
                        [
                            'to' => $phone
                        ]
                    ],
                    'text' => "Votre code de confirmation est : ".$code
                ]
            ]
        ];

        $apiUrl = $this->api_url.'/sms/2/text/advanced';

        Log::info("InfoBipService SMS - URL et payload:", [
            "base_url" => $this->api_url,
            "full_url" => $apiUrl,
            "body" => $body
        ]);
        $response = Http::withHeaders($this->headers)->post($apiUrl, $body);

        Log::info("info d'envoi:", [
            'url' =>  $apiUrl ]);

        Log::info("Réponse de l'envoi du SMS:", [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        if($response->status() != 200){
            return [
                'status' => false,
                'message' => "Une erreur est survenue"
            ];
        } else {
            return [
                'status' => true,
                'message' => "Le message a été envoyé"
            ];
        }
    }


    public function sendWhatsappSms(string $code, string $phone): array
    {
        Log::info('Envoi du message WhatsApp au '.$phone);

        $phone = $this->refactorPhoneNumber($phone);

        $body = [
            "application_id"=>  "charm-whatsapp" ,

            'messages' => [
                [
                    'from' => $this->sender,
                    'to' => $phone,
                    'content' => [
                        'templateName' => 'authentication',
                        'templateData' => [
                            'body' => [
                                'placeholders' => [$code],
                            ],
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'parameter' => $code,
                                ],
                                [
                                    'type' => 'QUICK_REPLY',
                                    'parameter' => "confirmer",
                                ]
                            ],
                            
                        ],
                        'language' => 'fr',
                    ],
                ],
            ],
        ];

        $apiUrl = $this->api_url.'/whatsapp/1/message/template';

        Log::info('InfoBipService WhatsApp - URL et payload:', [
            'base_url' => $this->api_url,
            'full_url' => $apiUrl,
            'body' => $body,
        ]);

        $response = Http::withHeaders($this->headers)->post($apiUrl, $body);

        Log::info('Réponse WhatsApp OTP', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response->successful()
            ? ['status' => true, 'message' => 'Le message a été envoyé']
            : ['status' => false, 'message' => 'Une erreur est survenue'];
    }

}
                
