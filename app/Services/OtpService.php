<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Events\SendEmailOtpEvent;
use App\Events\SendOtpByInfoBipEvent;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private InfoBipService $infoBipService;
    private TermiiService $termiiService;
  
    public function __construct(
        InfoBipService $infoBipService,
        TermiiService $termiiService
    ) {
        $this->infoBipService = $infoBipService;
        $this->termiiService = $termiiService;
    }
    /**
    * generate and send otp .
    *
    * @param User $user
    * @return Array|Null
    */
    public function generate(User $user) : array | Null
    {
        $arr = ["otp"=> rand(100000, 999999) , "otp_expires_at"=> Carbon::now()->addMinutes(10)];
        $user = (new UserRepository(app()))->update($arr , $user->id); 
        event(new SendEmailOtpEvent($user));
        
        return Null ;
    }


    /**
    * generate  otp code
    *
    * @return string
    */
    public function gen() : string
    {
       $currentOTP = random_int(100000, 999999);
       return (string) $currentOTP; 
    }

    /**
    * send otp code via sms
    * @param string $code
    * @param string $phoneNumber
    *
    * @return string
    */
    public function sendSMS(string $code , string $phoneNumber)
    {
        // Stocker dans le cache avec expiration de 5 minutes
        Cache::put('otp_' . $phoneNumber, Hash::make($code), now()->addMinutes(5));
        $provider = $this->resolveSmsProvider();
        Log::info('code envoye via sms', [
            'provider' => $provider,
            'phone_number' => $phoneNumber,
        ]);

        $result = $this->sendSmsWithProvider($provider, $code, $phoneNumber);
        Log::info('resultat envoi otp sms', [
            'provider' => $provider,
            'result' => $result,
        ]);

        event(new SendOtpByInfoBipEvent($code , $phoneNumber));
        
        return 'If an account exists with this phone number, a reset link will be sent.' ;
    }

    /**
    * send otp code via sms
    * @param string $code
    * @param string $phoneNumber
    *
    * @return string
    */
    public function sendByWhatsapp(string $code , string $phoneNumber)
    {
        // Stocker dans le cache avec expiration de 5 minutes
        Cache::put('otp_' . $phoneNumber, Hash::make($code), now()->addMinutes(5));
        $result = $this->infoBipService->sendWhatsappSMS($code , $phoneNumber);
        Log::info('resultat envoi otp whatsapp', [
            'provider' => 'infobip',
            'result' => $result,
        ]);
        event(new SendOtpByInfoBipEvent($code , $phoneNumber,'wh'));
        return 'If an account exists with this phone number, a reset link will be sent.' ;
    }

    private function resolveSmsProvider(): string
    {
        // Priorite au setting runtime si present, sinon valeur env/config
        $provider = strtolower((string) setting(
            'otp_sms_provider',
            config('services.otp.sms_provider', 'infobip')
        ));

        if (!in_array($provider, ['infobip', 'termii'], true)) {
            Log::warning('otp_sms_provider invalide, fallback vers infobip', [
                'otp_sms_provider' => $provider,
            ]);

            return 'infobip';
        }

        return $provider;
    }

    private function sendSmsWithProvider(string $provider, string $code, string $phoneNumber): array
    {
        return match ($provider) {
            'termii' => $this->termiiService->sendSMS($code, $phoneNumber),
            default => $this->infoBipService->sendSMS($code, $phoneNumber),
        };
    }

}
