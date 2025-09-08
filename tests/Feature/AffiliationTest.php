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

            $user = User::create([
                'name' => 'user1 test',
                'email' => 'user1E82@example.com',
                'phone_number' => '+00228900409982',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->assignRole(3);

            $response =  $this->actingAs($user2, 'api')->postJson(route('api.affiliates.generate'));

            $responseData = $response->json();
            Log::info([
                'status' => $response->status(),   // code HTTP
                'response' => $responseData        // contenu réel
            ]);

            
            $response1 =  $this->actingAs($user, 'api')->getJson(route('api.affiliates.confirm',$responseData['data']['code']));

            $response1Data = $response1->json();
            
            Log::info([
                'status' => $response1->status(),   // code HTTP
                'response' => $response1Data        // contenu réel
            ]);

            Log::info( Wallet::where('user_id',$user2->id)->get() ) ;
            Log::info( Wallet::where('user_id',$user->id)->get() ) ;
        

            $response1->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
