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
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class OtpService
{
    private $userRepository;

    public function __construct() {}

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
          
        event(new SendOtpByInfoBipEvent($code , $phoneNumber,'wh'));
        return 'If an account exists with this phone number, a reset link will be sent.' ;
    }


}
