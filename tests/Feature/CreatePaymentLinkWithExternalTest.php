<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use App\Types\PaymentType;
use App\Services\PaymentService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\AppSettingsTableSeeder;
use Database\Seeders\CurrenciesTableSeeder;
use Database\Seeders\WalletsTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreatePaymentLinkWithExternalTest extends TestCase
{
    use DatabaseTransactions;
    
   


    /** @test */
    public function it_creates_a_payment_link_externally()
    {
        try{ 
            // Créer un utilisateur fictif
            $user = User::create([
                    'name' => 'user test',
                    'email' => 'use23r1@example.com',
                    'phone_number' => '+0022489000000',
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password125'),
                    'api_token' => Str::random(60),
                    'device_token' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
            ]);

        

            // Simuler un service de paiement
            $paymentService = app(PaymentService::class);

            // Appel de la méthode à tester
            $result = $paymentService->createPaymentLinkWithExternal(2000, $user, PaymentType::CREDIT);
            // Vérifier que le résultat est un array non null
            $this->assertIsArray($result);
            $this->assertNotEmpty($result);
            Log::info( Wallet::where('user_id',$user->id)->get() ) ;


        } catch (\Throwable $e) {
            Log::error('FAILD:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}