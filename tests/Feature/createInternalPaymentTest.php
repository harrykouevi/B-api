<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\PurchaseRepository;
use App\Services\PaymentService;
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
            $clientW = Wallet::find('2df056af-cbdd-416a-8f6c-4cb6ef02bc3f');
            $receiver = User::find(29) ;
            $purchase = Purchase::find(38) ;

            // $purchasepayment = app(PaymentService::class)->createPayment(1000,$clientW ,$receiver,Null,$purchase->taxes);
            // $purchasepayment = $purchasepayment[0];

            
            dd( [
                $clientW->toArray(),
                $receiver->toArray(),
                $purchase->toArray(),
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
