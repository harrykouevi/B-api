<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserAPIControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
         try{ 
           
           
            $response = $this->postJson('api/register', [
                'name' => Str::random(10),
                'email' => Str::random(10).'@exampdle.com',
                'phone_number' => '+00228'.Str::random(8),
                'password'  => 'password125',
                'password_confirmation'  => 'password125'
                
                
                    ]);

           
            $data = $response->json();
            // dd($data) ;
            $user = User::where('id',$data['data']['id'])->first()  ;
           
            dd( Wallet::where('user_id',$user->id)->get() ) ;
            // $this->assertNotEmpty($uploadedUuid);

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
            throw $e; 
        }
    }

     /**
     * A basic feature test example.
     */
    public function test_forowner(): void
    {
         try{ 
           
           
            $response = $this->postJson('api/salon_owner/register', [
                'name' => Str::random(10),
                'email' => Str::random(10).'@exampdle.com',
                'phone_number' => '+00228'.Str::random(8),
                'password'  => 'password125',
                'password_confirmation'  => 'password125'
                
                
                    ]);

           
            $data = $response->json();
            // dd($data) ;
            $user = User::where('id',$data['data']['id'])->first()  ;
           
            dd( Wallet::where('user_id',$user->id)->get() ) ;
            // $this->assertNotEmpty($uploadedUuid);

            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
            throw $e; 
        }
    }
}
