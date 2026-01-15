<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AffiliationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        try{
            

            $user = User::create([
                'name' => 'user1 test',
                'email' => 'useZr1E82@example.com',
                'phone_number' => '+002282900409982',
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
                'email' => 'userS2E82@example.com',
                'phone_number' => '+002289300409988',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user2->assignRole(3);

            $user3 = User::create([
                'name' => 'userddEdd3 test',
                'email' => 'userS2E823@example.com',
                'phone_number' => '+0022893003',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user3->assignRole(3);

            $user4 = User::create([
                'name' => 'userddEdd4 test',
                'email' => 'userS2E824@example.com',
                'phone_number' => '+0022893004',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user4->assignRole(2);

            $response =  $this->actingAs($user, 'api')->postJson(route('api.affiliates.generate'));

            $responseData = $response->json();
            Log::info([
                'status' => $response->status(),   // code HTTP
                'response' => $responseData        // contenu réel
            ]);

            
            $response1 =  $this->actingAs($user2, 'api')->getJson(route('api.affiliates.confirm',$responseData['data']['code']));

            $response1Data = $response1->json();
            
            Log::info([
                'status' => $response1->status(),   // code HTTP
                'response' => $response1Data        // contenu réel
            ]);


            $response3 =  $this->actingAs($user3, 'api')->getJson(route('api.affiliates.confirm',$responseData['data']['code']));

            $response3Data = $response3->json();
            
            Log::info([
                'status' => $response3->status(),   // code HTTP
                'response' => $response3Data        // contenu réel
            ]);

            $response4 =  $this->actingAs($user4, 'api')->getJson(route('api.affiliates.confirm',$responseData['data']['code']));

            $response4Data = $response4->json();
            
            Log::info([
                'status' => $response4->status(),   // code HTTP
                'response' => $response4Data        // contenu réel
            ]);

            Log::info( Wallet::where('user_id',$user2->id)->get() ) ;
            Log::info( Wallet::where('user_id',$user->id)->get() ) ;
        

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
