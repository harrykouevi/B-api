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


class ResetLinkMmailTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');
        try{ 
           
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

            $response =  $this->actingAs($user2, 'api')->postJson(route('api.users.sendresetlinkemail'), [
                "email" => 'user2E82@example.com',
                // 'payment' => ['amount'=> 200 ],
            ]);

            Log::info([
                'response' => $response->json()       // contenu réel
            ] );

           
            $response2 =  $this->actingAs($user, 'api')->postJson(route('api.users.sendresetlinkemail'), [
                "email" => 'user222@example.com',
                // 'taxe'  =>  ["value" => 10, "type" => "percent"]
                            
            ]);

            Log::info([
                'response' => $response2->json()       // contenu réel
            ] );

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
