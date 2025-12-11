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
        $body = [
            'messages' => [
                [
                    'sender' => $this->sender,
                    'destinations' => [
                        [
                            'to' => $phone
                        ]
                    ],
                    'content' => [        
                             'text' => "Votre code de confirmation est : ".$code." ",              
                    ],
                ]
            ]
        ];
        Log::info("InfoBipService message:", [
            "headers:  " =>  json_encode($this->headers),
            "body:  " =>  json_encode($body)
        ]);
        $apiUrl = $this->api_url.'/sms/3/messages';
        $response = Http::withHeaders($this->headers)->post($apiUrl, $body);
        Log::info("réponse de l'envoie de l'sms: ",['response' => $response]);
        if($response->status() != 200){
            return [
                'status' => false,
                'message' => "Une erreur est survenue"
            ];
            }else{
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
            'messages' => [
                [
                    'from' => $this->sender,
                    'to' => $phone,
                    'content' => [
                        'templateName' => 'charm',
                        'templateData' => [
                            'body' => [
                                'placeholders' => [$code],
                            ],
                            'buttons' => [
                                [
                                    'type' => 'COPY_CODE',
                                    'parameter' => $code,
                                ],
                            ],
                            
                        ],
                        'language' => 'fr',
                    ],
                ],
            ],
        ];

        $apiUrl = $this->api_url.'/whatsapp/1/message/template';
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
                
