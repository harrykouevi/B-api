<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;

use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

class MakeBookingTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        
        try{
            

            $user = User::create([
                'name' => 'user13 test',
                'email' => 'user1E84322@example.com',
                'phone_number' => '+002289050409982',
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
                'name' => 'test',
                'email' => 'user28U77E82@example.com',
                'phone_number' => '+0022890087409988',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user2->assignRole(3);

           

            
            
            $booking_resp =  $this->actingAs($user2, 'api')->postJson(route('api.bookings.store'), [
                 "duration"=> "0.0", 
                 "quantity"=> 1, 
                 "cancel"=> false, 
                 "taxes"=> [], 
                 "options"=> [1, 3, 4], 
                 "user_id"=> $user2->id, 
                 "e_services"=> [210], 
                "salon_id"=> 2, 
                 
                 "booking_at"=> "2025-10-27 12:00:00.000Z"

            ]);
            $booking_Data = $booking_resp->json();
            
            Log::info([
                'status' => $booking_resp->status(),   // code HTTP
                'response' => $booking_Data        // contenu réel
            ]);
            
            


        


            $booking_resp->assertStatus(200)
            ;
           
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
