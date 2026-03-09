<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


use Tests\TestCase;

class PostApiControllerTest extends TestCase
{
    // use DatabaseTransactions;
    /**
     * A basic feature test example.
     */
    public function testStore(): void
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

            // $file = UploadedFile::fake()->image('water.jpg');
         
            $file = new UploadedFile(
                base_path('tests/video.mp4'),
                'video.mp4',
                'video/mp4',
                null,
                true // test mode
            );
            
            $this->actingAs($user,'api');
           
            $response = $this->actingAs($user, 'api')->postJson(route('api.posts.store'), [
             
                "author_id"=> $user->id, 
                // "e_service_id"=> 210, 
                // "salon_id"=> 62, 
                'caption' => 'test 1',
                'media' => [$file], // nom de la collection
            ]);

            dd($response->json());
            // $this->assertNotEmpty($uploadedUuid);

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function testAddView()
    {

        try{ 
          
            $user = $this->createUser();
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

            $data = [
                'post_id' =>   $post->id ,
                'user_id' =>  $user->id 
            ];

            $response = $this->postJson(route('api.posts.addview',$post->id), $data);
            $data = $response->json();

         
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }

        
    }

    public function testMyFavoritePosts()
    {

        try{ 
          
            $user = $this->createUser();
            $queryParameters = [
                // 'with' => 'post',
                // 'search' => 'categories.id:3',
                // 'searchFields' => 'categories.id:=',
            ];
            
    
            $this->actingAs($user,'api');
            $response = $this->json('get', route('api.me.posts.favorite') , $queryParameters);
        
             $d = $response->json();
             dd($d) ;
         
            $response->assertStatus(ResponseAlias::HTTP_OK);

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

    public function testlike()
    {

        try{ 
          
            $user = $this->createUser();
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

     
            $response = $this->postJson(route('api.posts.like',$post->id));
            $data = $response->json();

         
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }

        
    }

    public function testComment()
    {

        try{ 
          
            $user = $this->createUser();
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

            $data = [
                'content' =>   "un commentaire depose" ,
            ];

            $response = $this->postJson(route('api.posts.storecomment',$post->id), $data);
            $data = $response->json();

         
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }

        
    }

    public function testGetComments()
    {


        try{ 
          
            $user = $this->createUser();
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

            
            for($i=0; $i < 10; $i++){
                $data = [
                    'content' =>   "un commentaire depose $i" ,
                ];
                $this->postJson(route('api.posts.storecomment',$post->id), $data);
                
            }
            
            $queryParameters = [
                // 'with' => 'post',
                // 'search' => 'categories.id:3',
                // 'searchFields' => 'categories.id:=',
            ];
            
            $response = $this->json('get', route('api.posts.comments',$post->uuid) , $queryParameters);
        
            $d = $response->json();
             dd($d) ;
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }

        
    }

     private function createUser(){
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


    private function createPost(User $user){
            // Créer un utilisateur et se connecter
        $user = Post::create([
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
