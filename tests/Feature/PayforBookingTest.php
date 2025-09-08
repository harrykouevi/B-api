<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayforBookingTest extends TestCase
{
    use DatabaseTransactions;
    /** @test */
    public function PayforBooking(): void
    {
        try {
             $user = User::create([
                    'name' => 'userdddd test',
                    'email' => 'user222@example.com',
                    'phone_number' => '+0022890009988',
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password125'),
                    'api_token' => Str::random(60),
                    'device_token' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);

            $user->assignRole(2);

            $user2 = User::create([
                    'name' => 'userddEdd test',
                    'email' => 'user2E82@example.com',
                    'phone_number' => '+00228900409988',
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password125'),
                    'api_token' => Str::random(60),
                    'device_token' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);

            $user2->assignRole(3);

            $currency = Currency::find(1) ;
            
            $wallet = Wallet::create([
                   
                    'name'  => 'Igris',
                    'balance' => 5000,
                    'currency' =>  $currency,
                    'user_id' => $user->id,
                    'enabled' => 1 ,
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);
         
            $booking = Booking::create([
                    
                    'e_services' => [
                            ['id' => 3, 'name' => 'Mid Fade Taper', 'price' => 1500, 'discount_price' => 0]
                        ],
                    'salon' => [ 'id' => 2,
                            'name' => 'Eugène Salon',
                            'phone_number' => '+22896133362',
                            'mobile_number' => '+228'],
                    'employee_id' => $user->id,
                    'quantity' =>  1,
                    'user_id' => $user->id,
                    'booking_status_id' =>  1,
                    'created_at' => now(),
                    'updated_at' => now(),
                  
            ]);


            $booking3 = Booking::create([
                    
                    'e_services' => [
                            ['id' => 3, 'name' => 'Mid Fade Taper', 'price' => 1500, 'discount_price' => 0]
                        ],
                    'salon' => [ 'id' => 2,
                            'name' => 'Eugène Salon',
                            'phone_number' => '+22896133362',
                            'mobile_number' => '+228'],
                    'employee_id' => $user2->id,
                    'quantity' =>  1,
                    'user_id' => $user2->id,

                    'booking_status_id' =>  1,
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);




            $booking2 = Booking::create([
                    'e_services' => [
                            ['id' => 3, 'name' => 'Mid Fade Taper', 'price' => 1500, 'discount_price' => 0]
                        ],
                    'salon' => [ 'id' => 2,
                            'name' => 'Eugène Salon',
                            'phone_number' => '+22896133362',
                            'mobile_number' => '+228']
                        ,
                    'employee_id' => $user->id,
                    'quantity' =>  1,
                    'user_id' => $user->id,

                    'booking_status_id' =>  1,
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);


            $response =  $this->actingAs($user, 'api')->postJson(route('api.payments.wallets', $wallet->id), [
                    "id" => $booking2->id,
                    'payment' => ['amount'=> 200 ],
                ]);

            // $responseData = $response->json();
            // Log::info([
            //     'status' => $response->status(),   // code HTTP
            //     'response' => $responseData        // contenu réel
            // ]);
                 Log::info( Wallet::find($wallet->id) ) ;
            $response->assertStatus(200);
           
        } catch (\Throwable $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
