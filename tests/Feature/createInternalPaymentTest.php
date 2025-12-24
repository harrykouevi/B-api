<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\PurchaseRepository;
use App\Services\PaymentService;
use App\Types\WalletType;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class createInternalPaymentTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        try{ 
           //321
           //315
           //333
            //313
            $payer = setting('app_default_wallet_id');
            $receiver = User::find(311) ;
            $amount = 10000 ;
            // $purchase = Purchase::find(38) ;

            $payment = app(PaymentService::class)->createPayment($amount,$payer ,$receiver,  WalletType::PRINCIPAL);
            $payment__ = $payment[0];

            
            dd( [
                $payer,
                $receiver->toArray(),
                WalletType::PRINCIPAL ,
                $payment__->toArray(),
                // 'payment_id' => $purchasepayment ? $purchasepayment->id : 'NULL'
            ]);
            // if($purchasepayment){
            //     //mise à jour du purchase comme étant payé et validé
            //     $purchase = app(PurchaseRepository::class)->update(['payment_id' => $purchasepayment->id , 'purchase_status_id' => 2  ], $purchase->id);
            // }

        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
