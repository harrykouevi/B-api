<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Events\SendEmailOtpEvent;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
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


}
