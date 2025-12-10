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
        $this->api_url = config('services.infobip.api_url');
        $this->api_key = config('services.infobip.api_key');
        $this->sender = config('services.infobip.sender');
        $this->headers = [
            'Content-Type: application/json',
            'Authorization: App '.$this->api_key,
        ];
        
     }

 

    public function sendSms($phone,$code):  array{
        Log::info('Envoi du message au  '.$phone);

        $this->body = [
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
            "body:  " =>  json_encode($this->body)
        ]);
         $this->api_url = $this->api_url.'/sms/3/messages';
       
        $response = Http::withHeaders($this->headers)->post($this->api_url, $this->body);
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


    public function sendWhatsappSms($phone, $code): array
{
    Log::info('Envoi du message WhatsApp au '.$phone);



    $body = [
        'messages' => [
            [
                'from' => $this->sender,
                'to' => $phone,
                'content' => [
                    'templateName' => 'Code de vérification',
                    'templateData' => [
                        'body' => [
                            'placeholders' => [$code],
                        ],
                    ],
                    'language' => 'fr',
                ],
            ],
        ],
    ];

    $apiUrl = rtrim($this->api_url, '/').'/whatsapp/1/message/template';
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
                