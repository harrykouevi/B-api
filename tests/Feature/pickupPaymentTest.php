<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Types\WalletType;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\PaymentService;


use Illuminate\Support\Facades\Hash;

class pickupPaymentTest extends TestCase
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
            $currency = Currency::find(1) ;
            
            $wallet2 = Wallet::create([
                'name'  => 'Bonus',
                'balance' => 5000,
                'currency' =>  $currency,
                'user_id' => $user2->id,
                'enabled' => 1 ,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wallet2 = Wallet::where('name', '==', WalletType::PRINCIPAL->value)->where('user_id', '==', $user2->id)->first() ;
            dd($wallet2->balance) ;

            $balanceUser = [] ;
            $a = [321,315,333,313,311,317,329,307, 312 ,263,377,237 , 230 , 380 ] ;
            foreach($a as $key){
                $wa = Wallet::where('name', '==', WalletType::PRINCIPAL->value)->where('user_id', '==', $user2->id)->first() ;
                 
                $payer = $wa ;
                $amount = $wa->balance ;
                $payment = app(PaymentService::class)->createPayment($amount,$payer );
                $payment__ = $payment[0];

                $balanceUser[] = ['user'=> $user2->id , 'montant'=> $amount , 'payement'=> $payment__->toArray()]  ;

            }
            dd( $balanceUser);

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
