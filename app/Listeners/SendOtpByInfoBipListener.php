<?php

namespace App\Listeners;

use App\Events\SendOtpByInfoBipEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;



class SendOtpByInfoBipListener
{
    public string $code ;
    public string $phoneNumber ;
    public string $provider ;
    public string $_apiKey = 'bba4558d9e99eb22b1624c09bc3bc1d4-17a91549-9d23-4598-b8f3-dd4d81104792';
            
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
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
            
            if($event->provider == "sms"){ 
                $response = $this->bySms();
                Log::channel('otp_sending')->info($response ) ;

                if ($response->successful()) {
                    Log::channel('otp_sending')->info("Reset OTP was sent successfully to infobip for $event->phoneNumber with provider : $event->provider ");
                } else {
                    Log::channel('otp_sending')->warning("Reset OTP  link not sent , please Try again . with provider : used provider $event->provider");
                }
            }
            if($event->provider == "wh"){ 
                $response = $this->byWhasapp();
                Log::channel('otp_sending')->info($response ) ;
                if ($response->successful()) {
                    Log::channel('otp_sending')->info("Reset OTP was sent successfully to infobip for $event->phoneNumber with provider : $event->provider ");
                } else {
                    Log::channel('otp_sending')->warning("Reset OTP  link not sent , please Try again . with provider : used provider $event->provider");
                }
            }
           
        } catch (\Exception $e) {
            // Gestion de l'exception
            Log::channel('otp_sending')->error('Erreur lors de l\' envoi du code OTP à l\'utilisateur #' . $event->user->id, [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Provider SMS.
     * @return \Illuminate\Http\Client\Response
     * 
     */
    private function bySms() : \Illuminate\Http\Client\Response
    {
        $data = [
            'messages' => [
                [
                    "sender"=> "Charm",
                    'destinations' => [
                        ['to' => $this->phoneNumber]
                    ],
                    // 'from' => 'Charm',
                    "content" => ['text' => "Votre code de vérification est: $this->code . Ce code est à usage unique et expirera prochainement."]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization'=> 'App ' .  $this->_apiKey,
            'Content-Type'=> 'application/json',
        ])
        ->post('https://api.infobip.com/sms/3/messages', $data);


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
            
            "from"=> "22896617963",
            "to"=> $this->phoneNumber,
            "content" => ['text' => "Votre code de vérification est: $this->code . Ce code est à usage unique et expirera prochainement."]

        ];

        $response = Http::withHeaders([
            'Authorization'=> 'App ' .  $this->_apiKey,
            'Content-Type'=> 'application/json',
        ])
        ->post('https://api.infobip.com/whatsapp/1/message/text', $data);

        return $response ;

    }
}
