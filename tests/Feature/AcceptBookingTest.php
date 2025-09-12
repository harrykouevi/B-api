<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\PurchaseRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AcceptBookingTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        try{ 
            Log::info(Purchase::all() ) ;
            $currency = Currency::find(1) ;

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

            $wallet1 = Wallet::create([
                    
                    'name'  => 'Igris',
                    'balance' => 5000,
                    'currency' =>  $currency,
                    'user_id' => $user->id,
                    'enabled' => 1 ,
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);

            $user2 = User::create([
                    'name' => 'test',
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


            $wallet2 = Wallet::create([
                    
                    'name'  => 'Bonus',
                    'balance' => 5000,
                    'currency' =>  $currency,
                    'user_id' => $user2->id,
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
                    'employee_id' => $user2->id,
                    'quantity' =>  1,
                    'user_id' => $user2->id,
                    'booking_status_id' =>  1,
                    'booking_at' => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                    
            ]);

            $response =  $this->actingAs($user2, 'api')->postJson(route('api.payments.wallets', $wallet2->id), [
                "id" => $booking->id,
                'payment' => ['amount'=> 200 ],
            ]);

            Log::info([
                'response' => $response->json()       // contenu réel
            ] );

           
            $response2 =  $this->actingAs($user, 'api')->putJson(route('api.bookings.update', $booking->id), [
                'booking_status_id' =>  4 ,
                'taxes'  =>  ["value" => 10, "type" => "percent"]
                            
            ]);

            
         

            // $o=   app(PurchaseRepository::class)->all() ;
            // Log::info(Booking::all() ) ;
            Log::info(Purchase::all() ) ;
            Log::info( Wallet::find($wallet2->id) ) ;
            Log::info( Wallet::find($wallet1->id) ) ;

            $response2->assertStatus(200);


        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

}
