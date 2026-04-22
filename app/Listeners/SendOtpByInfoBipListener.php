<?php

namespace App\Listeners;

use App\Events\SendOtpByInfoBipEvent;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;



class SendOtpByInfoBipListener
{
    public string $code ;
    public string $phoneNumber ;
    public string $provider ;
    public string $_apiKey;
    public string $_baseUrl;
    public string $_smsSender;
    public string $_whatsappSender;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        $this->_apiKey = config('services.infobip.api_key') ;
        $this->_baseUrl = env('INFOBIP_BASE_URL', 'https://api.infobip.com');
        $this->_smsSender = env('INFOBIP_SENDER');
        $this->_whatsappSender = env('INFOBIP_SENDER', '22896617963');
    }

    /**
     * Handle the event.
     */
    public function handle( SendOtpByInfoBipEvent $event): void
    {
        $this->code = $event->code ;
        $this->phoneNumber = $event->phoneNumber ;
        $this->provider = $event->provider ;

        try {
            Log::info('in listener');
            
            if($event->provider == "sms"){ 
                $response = $this->bySms();
                $this->logProviderResponse($response, 'sms');

                if ($response->successful()) {
                    Log::channel('otp_sending')->info("Reset OTP was sent successfully to infobip for $event->phoneNumber with provider : $event->provider ");
                } else {
                    Log::channel('otp_sending')->warning("Reset OTP  link not sent , please Try again . with provider : used provider $event->provider");
                }
            }
            if($event->provider == "wh"){ 
                $response = $this->byWhasapp();
                $this->logProviderResponse($response, 'whatsapp');
                if ($response->successful()) {
                    Log::channel('otp_sending')->info("Reset OTP was sent successfully to infobip for $event->phoneNumber with provider : $event->provider ");
                } else {
                    Log::channel('otp_sending')->warning("Reset OTP  link not sent , please Try again . with provider : used provider $event->provider");
                }
            }
           
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('otp_sending')->error('Erreur lors de l\'envoi du code OTP', [
                'phone_number' => $event->phoneNumber,
                'provider' => $event->provider,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Provider SMS.
     * @return \Illuminate\Http\Client\Response
     * 
     */
    private function bySms() 
    {

        return Http::response([
            'status' => 'skipped',
            'message' => 'SMS sending disabled (bypass mode)'
        ], 200);

        $data = [
            'messages' => [
                [
                    "sender"=> $this->_smsSender,
                    'destinations' => [
                        ['to' => $this->phoneNumber]
                    ],
                    "content" => ['text' => "Votre code de vérification est: $this->code . Ce code est à usage unique et expirera prochainement."]
                ]
            ]
        ];

        Log::info("listenner info d'envoi:", [
            'url' =>  $this->_baseUrl . '/sms/3/messages' ]);

        $response = Http::withHeaders([
            'Authorization'=> 'App ' .  $this->_apiKey,
            'Content-Type'=> 'application/json',
        ])
        ->post($this->_baseUrl . '/sms/3/messages', $data);


        return $response ;

    }


    /**
    * Provider Whatsapp.
    * @return \Illuminate\Http\Client\Response
    * 
    */
    private function byWhasapp() : \Illuminate\Http\Client\Response
    {
        $data = [
            // "applicationId"=>  "default" ,
            "from"=> $this->_whatsappSender,
            "to"=> $this->phoneNumber,
            "content" => ['text' => "Votre code de vérification est: $this->code . Ce code est à usage unique et expirera prochainement."]
            
        ];

        Log::info("listenner info d'envoi:", [
            'url' =>  $this->_baseUrl . '/whatsapp/1/message/text' ]);


        $response = Http::withHeaders([
            'Authorization'=> 'App ' .  $this->_apiKey,
            'Content-Type'=> 'application/json',
        ])
        ->post($this->_baseUrl . '/whatsapp/1/message/text', $data);

        

        Log::info('Réponse WhatsApp OTP listen', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
        return $response ;

    }

    private function logProviderResponse(Response $response, string $provider): void
    {
        Log::channel('otp_sending')->info('OTP provider response', [
            'provider' => $provider,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }
}
