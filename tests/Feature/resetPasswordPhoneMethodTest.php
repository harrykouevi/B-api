<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Cache;



class resetPasswordPhoneMethodTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        try{

            $phone = '+22896617963' ;
             $user = User::create([
                    'name' => 'userdddd test',
                    'email' => 'new124@example.com',
                    'phone_number' => $phone,
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => 'password125',
                    'api_token' => Str::random(60),
                    'device_token' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);
            Log::info( User::where('id',$user->id)->get() ) ;


            $currentOTP = (string) "123456";
            // Stocker dans le cache avec expiration de 5 minutes
            Cache::put('otp_' . $phone, Hash::make($currentOTP), now()->addMinutes(5));
          
            $response =  $this->postJson(route('api.users.password.phone.reset'), [
                "phone_number" => $phone,
                "password" => "martiness",
                "password_confirmation" => "martiness",
                "reset_code"  => "123456"
            ]);

            Log::info( User::where('id',$user->id)->get() ) ;
            Log::info([
                'response' => $response->json()       // contenu réel
            ] );

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
