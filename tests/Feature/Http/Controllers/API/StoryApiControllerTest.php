<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Story;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class StoryApiControllerTest extends TestCase
{
    // use DatabaseTransactions;


    public function test_(): void
    {
        try{ 
            // Créer un utilisateur et se connecter
            $user = $this->createUser(2);
            
            $this->actingAs($user,'api');
            $response = $this->getJson(route('api.stories.index'));

            // dd($response->json());
            $response->assertStatus(200);
           
            $response->assertStatus(ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * A basic feature test example.
     */
    public function test_store(): void
    {
        try{ 
            // Fake le storage pour Spatie Media Library ^pour que l'image soit supprimer
            // Storage::fake('public');

            // Créer un utilisateur et se connecter
            $user = $this->createUser(2);

            // $file = UploadedFile::fake()->image('water.jpg');
         
            $file = new UploadedFile(
                base_path('tests/video.mp4'),
                'video.mp4',
                'video/mp4',
                null,
                true // test mode
            );
            
            $this->actingAs($user,'api');
           
            $response = $this->actingAs($user, 'api')->postJson(route('api.stories.store'), [
             
                "user_id"=> $user->id, 
                'file' => [$file], // nom de la collection
            ]);

            dd($response->json());

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function testShow(){
        
        try{ 
          
            $user = $this->createUser();
            $queryParameters = [
                // 'with' => 'post',
                // 'search' => 'categories.id:3',
                // 'searchFields' => 'categories.id:=',
            ];
            
    
            $this->actingAs($user,'api');
            $response = $this->json('get', route('api.posts.show','3e8bcb14-c8e4-46ab-8aa9-1589d7143895') , $queryParameters);
        
            $d = $response->json();
            dd($d) ;
         
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function createUser(int $role = 2){
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

        return $user ;
    }


    private function createStory(User $user){
            // Créer un utilisateur et se connecter
        $user = Story::create([
                "author_id"=> $user->id, 
                "e_service"=> 210, 
                "salon_id"=> 62, 
                "caption" =>  " zrzr rzzrz rzrzr rzrz",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return $user ;
    }

}
