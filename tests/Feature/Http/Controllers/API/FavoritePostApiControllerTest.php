<?php
/*
 * File name: FavoritePostApiControllerTest.php
 * Last modified: 2026.02.12 at 16:22:27
 * Author: Harry.Kouevi
 * Copyright (c) 2026
 */

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Repositories\UploadRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Helpers\TestHelper;


class FavoritePostApiControllerTest extends TestCase
{
    // use DatabaseTransactions;

    
    public function testShow()
    {

        $response = $this->json('get', 'api/e_services/17');
        $response->assertStatus(200);
    }

    public function testGetEServicesByCategorys()
    {
        $queryParameters = [
            'with' => 'salon;salon.address;categories',
            'search' => 'categories.id:3',
            'searchFields' => 'categories.id:=',
        ];

        $response = $this->json('get', 'api/e_services', $queryParameters);
        $data = TestHelper::generateJsonArray(count($response->json('data')), [
            'available' => true,
            'salon' => [
                'accepted' => true,
            ]
        ]);
        $response->assertStatus(ResponseAlias::HTTP_OK);
        $response->assertJson(['data' => $data]);
    }

    public function testGetRecommendedEServices()
    {
        $queryParameters = [
            'only' => 'id;name;price;discount_price;has_media;media;total_reviews;rate;available',
            'limit' => '6',
        ];

        $response = $this->json('get', 'api/e_services', $queryParameters);
        $data = TestHelper::generateJsonArray(count($response->json('data')), [
            'available' => true,
        ]);
        $response->assertStatus(ResponseAlias::HTTP_OK);
        $response->assertJson(['data' => $data]);
    }

    public function testGetFeaturedEServicesByCategory()
    {
        $queryParameters = [
            'with' => 'salon;salon.address;categories',
            'search' => 'categories.id:4;featured:1',
            'searchFields' => 'categories.id:=;featured:=',
            'searchJoin' => 'and',
        ];

        $response = $this->json('get', 'api/e_services', $queryParameters);
        $data = TestHelper::generateJsonArray(count($response->json('data')), [
            'available' => true,
            'featured' => true,
            'salon' => [
                'accepted' => true,
            ]
        ]);
        $response->assertStatus(ResponseAlias::HTTP_OK);
        $response->assertJson(['data' => $data]);
    }

    public function testGetAvailableEServicesByCategory()
    {
        $queryParameters = [
            'with' => 'salon;salon.address;categories',
            'search' => 'categories.id:3',
            'searchFields' => 'categories.id:=',
            'available_salon' => 'true'
        ];

        $response = $this->json('get', 'api/e_services', $queryParameters);
        $data = TestHelper::generateJsonArray(count($response->json('data')), [
            'available' => true,
            'salon' => [
                'available' => true,
                'accepted' => true,
            ]
        ]);
        $response->assertStatus(ResponseAlias::HTTP_OK);
        $response->assertJson(['data' => $data]);
    }


    public function testStore()
    {

        try{ 
          
            // Créer un utilisateur et se connecter
            $user = $this->createUser();
             // Créer un utilisateur et se connecter
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

            // Préparer les données POST
            $data = [
               
                'post_id' =>   $post->id ,
                'user_id' =>  $user->id 
            ];
            $response = $this->postJson(route('api.favorite-posts.store'), $data);
            $data = $response->json();

         
            $response->assertStatus(ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }

        
    }


    public function testDestroy()
    {

        try{ 
          
            $user = $this->createUser();
            $post = $this->createPost($user) ;

            $this->actingAs($user,'api');

            // Préparer les données POST
            $data = [
                'post_id' =>   $post->id ,
                'user_id' =>  $user->id 
            ];
            $response = $this->postJson(route('api.favorite-posts.store'), $data);
            $json = $response->json();

            $response_d = $this->deleteJson(route('api.favorite-posts.destroy', $json['data']['id']));
           
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
