<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Currency;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BookingReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class ReminderTest extends TestCase
{
// use DatabaseTransactions;
    
    /** @test */
    public function test_example()
    {
         try{ 
            $currency = Currency::find(1) ;

            $user = User::create([
                    'name' => 'thor12',
                    'email' => 'thor12@example.com112',
                    'phone_number' => '+002289909882',
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
                    'name' => 'test3323',
                    'email' => 'test3323@example.com',
                    'phone_number' => '+0022890R78263623',
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
                    'booking_at' => Carbon::now()->addMinutes(28)->format('Y-m-d H:i:s') ,
                    'created_at' => now(),
                    'updated_at' => now(),
                    
            ]);

            $this->actingAs($user2, 'api')->postJson(route('api.payments.wallets', $wallet2->id), [
                "id" => $booking->id,
                'payment' => ['amount'=> 200 ],
            ]);

            
            $this->actingAs($user2, 'api')->putJson(route('api.bookings.update', $booking->id), [
                'booking_status_id' =>  4 ,
                'taxe'  =>  ["value" => 10, "type" => "percent"]
            ]);

            
            // Simuler un service de rappel
            $bookingReminderService = app(BookingReminderService::class);

            // Appel de la méthode à tester
            $result = $bookingReminderService->scheduleAllReminders($booking);

            // Vérifier que le résultat est un array non null
        //     $this->assertIsArray($result);
        //     $this->assertNotEmpty($result);
            $jobs = DB::table('jobs')->get();

            dd(Queue::getDefaultDriver() , $jobs->toArray()) ;
        
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}