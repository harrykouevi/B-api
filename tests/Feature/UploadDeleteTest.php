<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;




class UploadDeleteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        try{
            Log::info(Upload::all() ) ;

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
            $response =  $this->actingAs($user2, 'api')->postJson('http://localhost/api/uploads/clear', [
                    "uuid" => 1449  
                ]);
        
            Log::info(Upload::all() ) ;
            
            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
