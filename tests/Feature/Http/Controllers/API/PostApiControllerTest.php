<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

use Tests\TestCase;

class PostApiControllerTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
         try{ 
            // Fake le storage pour Spatie Media Library ^pour que l'image soit supprimer
            // Storage::fake('public');

            // Créer un utilisateur et se connecter
            $user = User::create([
                'name' => Str::random(10),
                'email' => Str::random(10).'@exampdle.com',
                'phone_number' => '+00228'.Str::random(8),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password125'),
                'api_token' => Str::random(60),
                'device_token' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user->assignRole(2);

           


           
            $response = $this->actingAs($user, 'api')->postJson(route('api.posts.store'), [
                "author_id"=> $user->id, 
                "e_service"=> 210, 
                "salon_id"=> 62, 
                "caption" =>  " zrzr rzzrz rzrzr rzrz"
            ]);

            dd($response->json());
            $this->assertNotEmpty($uploadedUuid);

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
