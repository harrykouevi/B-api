<?php

namespace Tests\Feature\Http\Controllers\Api;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Wallet;
use App\Repositories\PostRepository;
use App\Repositories\UploadRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;



use Tests\TestCase;

class UploadApiControllerTest extends TestCase
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


            $file = UploadedFile::fake()->image('water.jpg');
            

            $this->actingAs($user,'api');

            // Préparer les données POST
            $data = [
                'file' => $file,
                'field' => 'image', // nom de la collection
            ];
            $response = $this->postJson(route('api.uploads.store'), $data);
            $uploadedUuid = $response->json('data');
            $this->assertNotEmpty($uploadedUuid);

            // Préparer les données POST
            $data = [
                'caption' => 'gefef feff ef rfrffgrr frf',
                'image' => [$uploadedUuid], // nom de la collection
            ];

            // Appeler la route HTTP
            $response1 = $this->postJson(route('api.posts.store'), $data);
            $response1->assertStatus(200);

            $cacheUpload = app(UploadRepository::class)->getByUuid($uploadedUuid);
            $media = $cacheUpload->getMedia('image')->first();
            Log::info([$media->getPath()]) ;
            
            dd([
                'response' => $response1->json()      // contenu réel
            ]);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
