<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\PurchaseRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AcceptBookingWirhCouponTest extends TestCase
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
                    'balance' => 500000,
                    'currency' =>  $currency,
                    'user_id' => $user2->id,
                    'enabled' => 1 ,
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);
            
            

            $booking_resp =  $this->actingAs($user2, 'api')->postJson(route('api.bookings.store'), [
                'code' =>  'HARR80',
                 "duration"=> "0.0", 
                 "quantity"=> 1, 
                 "cancel"=> false, 
                 "taxes"=> [], 
                 "options"=> [1, 3], 
                 "user_id"=> $user2->id, 
                 "e_services"=> [1], 
                "salon_id"=> 1, 
                 
                 "booking_at"=> "2025-10-27 12:00:00.000Z"

            ]);
            $booking_data = $booking_resp->json();


            Log::info( ["booking____data",$booking_data] ) ;

            $response =  $this->actingAs($user2, 'api')->postJson(route('api.payments.wallets', $wallet2->id), [
                "id" => $booking_data['data']['id'],
                'payment' => ['amount'=> 0 ],
            ]);
            $payement_data = $response->json();
            

           if($payement_data['success'] == true){
                $response2 =  $this->actingAs($user, 'api')->putJson(route('api.bookings.update', $booking_data['data']['id']), [
                    'booking_status_id' =>  4 ,
                    'taxe'  =>  ["value" => 10, "type" => "percent"],
                ]);

                
            

                $o=   app(PurchaseRepository::class)->all() ;
                Log::info($booking_data ) ;
                $p = Purchase::first()  ;
                Log::info( WalletTransaction::where('payment_id', $p->payment_id)->get() ) ;

                Log::info( Wallet::find($wallet2->id) ) ;
                Log::info( Wallet::find($wallet1->id) ) ;
                $response2->assertStatus(200);
            }else{
                dd($payement_data) ;
                $response->assertStatus(200);
            }

            


        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

}
