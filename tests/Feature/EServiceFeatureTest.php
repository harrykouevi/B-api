<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Salon;
use App\Models\ServiceTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Support\Str;
use Tests\TestCase;

class EServiceFeatureTest extends TestCase
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

            $user->assignRole(2);

           

            
            
            $salon_resp =  $this->actingAs($user, 'api')->postJson(route('api.salons.store'), [
                'name' => 'Salon test',
                'address_id' => Address::first()->id,
                'phone_number' => '+00228900409982',
                'mobile_number' => '+00228900409982',
                'availability_range' => 0.01,
                'accepted' => true
            ]);
            $salon_Data = $salon_resp->json();
            
            Log::info([
                'status' => $salon_resp->status(),   // code HTTP
                'response' => $salon_Data        // contenu réel
            ]);
            
            $servi_resp_1=  $this->actingAs($user, 'api')->postJson(route('api.e_services.store'), [
                'name' => 'pourri',
                'price' => '1000',
                'discount_price' => '1500',
                'duration' => '12:05',
                'description' => 'bla bla bla',
                'salon_id' => $salon_Data['data']['id'],
                'category_id' => Category::find(8)->id ,
             
            ]);
            $servi_Data_1 = $servi_resp_1->json();
            $servi_resp_1->assertStatus(200);

            Log::info([
                'status' => $servi_resp_1->status(),   // code HTTP
                'response' =>  $servi_Data_1       // contenu réel
            ]);


            $servi_resp_2=  $this->actingAs($user, 'api')->postJson(route('api.e_services.storeFromTemplate'), [
                
                'price' => '1000',
                'discount_price' => '1500',
                'duration' => '12:05',
                'description' => 'bla bla bla',
                'salon_id' => $salon_Data['data']['id'],
              
                'template_id' => 8 ,
            ]);
            $servi_Data_2 = $servi_resp_2->json();


            Log::info([
                'status' => $servi_resp_2->status(),   // code HTTP
                'response' =>  $servi_Data_2       // contenu réel
            ]);


            $servi_resp_2->assertStatus(200)
            ;
           
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
        
    }
}
