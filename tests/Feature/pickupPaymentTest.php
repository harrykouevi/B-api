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
    // use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        
        try{ 

            // $user2 = User::create([
            //         'name' => 'userddEdd test',
            //         'email' => 'user2E82@example.com',
            //         'phone_number' => '+00228900409988',
            //         'phone_verified_at' => now(),
            //         'email_verified_at' => now(),
            //         'password' => Hash::make('password125'),
            //         'api_token' => Str::random(60),
            //         'device_token' => '',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            // ]);

            // $user2->assignRole(3);
            // $currency = Currency::find(1) ;
            
            // $wallet2 = Wallet::create([
            //     'name'  => WalletType::PRINCIPAL->value,
            //     'balance' => 5000,
            //     'currency' =>  $currency,
            //     'user_id' => $user2->id,
            //     'enabled' => 1 ,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ]);
            // $wallet22 = Wallet::where('name', WalletType::PRINCIPAL->value)->where('user_id', $user2->id)->first() ;
            // if(!is_null( $wallet22)){
            //  dd([  $wallet22->balance]) ;
            // }

            $balanceUser = [] ;
            $a = [321,315,333,313,311,317,329,307, 312 ,263,377,237 , 230 , 380 ] ;
            $a = [] ;
            // foreach($a as $key){
               
            //     $balanceUser[] = ['user'=> $key ]  ;
                
            // }
            foreach($a as $key){
                $wa = Wallet::where('name', WalletType::PRINCIPAL->value)->where('user_id', $key)->first() ;
                if(!is_null($wa)){
                    $payer = $wa ;
                    $amount = $wa->balance ;
                    $payment = app(PaymentService::class)->createPayment($amount,$payer );
                    $balanceUser[] = ['user'=> $key , 'montant'=> $amount , 'payement'=> $payment[0]->toArray()]  ;
                }
            }
            dd([ $balanceUser]);
            $response->assertStatus(200);
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
        }
        $arr =  [
            0 => [
                "user" => 315 ,
                "montant" => 10000.0,
                "payement" => [
                    "amount" => 10000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 315,
                    "updated_at" => "2026-01-16T11:18:18.000000Z",
                    "created_at" => "2026-01-16T11:18:18.000000Z",
                    "id" => 388,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 => [
                            "id" => "7a88eb9e-10d5-40c8-9b74-21446fe2a2fd",
                            "amount" => 10000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "14e03100-d06e-4d3c-a05d-ffb3f23bf877",
                            "user_id" => 315,
                            
                            "user" => [
                                "id" => 315,
                                "name" => "darryl",
                                "email" => null,
                                "phone_number" => "+22892311824",
                                
                            ]
                        ],
                        1 => [
                            "id" => "dfee586d-fe2f-441a-819f-6d187302b246",
                            "amount" => 10000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ]
                    ]
                ]
            ],
            
            1 =>  [
            "user" => 333,
            "montant" => 0.0,
            
            ],
            2 =>  [
                "user" => 313,
                "montant" => 10000.0,
                "payement" => [
                    "amount" => 10000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 313,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 390,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 => [
                            "id" => "7f25559a-5c0c-41fb-9ad1-f77198f1b2d9",
                            "amount" => 10000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                        1 =>  [
                            "id" => "e08ed555-cafd-4b3f-b782-f1d2aa4686b6",
                            "amount" => 10000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "6168b04d-6c9e-4867-b6e4-45f9886ca4f9",
                            "user_id" => 313,
                            
                            "user" =>  [
                                "id" => 313,
                                "name" => "AKPARE SABINE",
                                "email" => null,
                                "phone_number" => "+22870462632",
                                
                            ]
                        ]
                    ]
                ]
            ],
            
            3 =>  [
                "user" => 311,
                "montant" => 10000.0,
                "payement" => [
                    "amount" => 10000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 311,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 391,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 =>  [
                            "id" => "56b0b57d-685b-48c4-9d98-6daa46e1c27b",
                            "amount" => 10000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                    
                        1 => [
                            "id" => "76a55a83-3d5b-4874-8733-02b68fd4dce0",
                            "amount" => 10000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "9b237048-0aae-4b12-bc10-9941107f1c56",
                            "user_id" => 311,
                            
                            "user" =>  [
                                "id" => 311,
                                "name" => "Nina",
                                "email" => null,
                                "phone_number" => "+22897516061",
                                
                            ]
                        ]
                    ]
                ]
            ],
            4 =>  [
                "user" => 317,
                "montant" => 0.0,
                
            ],
            5 =>  [
                "user" => 329,
                "montant" => 10000.0,
                "payement" =>  [
                    "amount" => 10000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 329,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 393,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 =>  [
                            "id" => "74dc0b4a-28ad-4ce5-9f30-334050671915",
                            "amount" => 10000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                        
                        1 =>  [
                            "id" => "9c192f25-e255-47cd-9fbf-d4456cba2605",
                            "amount" => 10000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "1336320b-8496-4344-97ae-c75f9528f56d",
                            "user_id" => 329,
                            "user" =>  [
                                "id" => 329,
                                "name" => "ABALO Favour",
                                "email" => null,
                                "phone_number" => "+22890928957",
                            ]
                        ]
                    ]
                ]
            ],
            6 =>  [
                "user" => 307,
                "montant" => 5000.0,
                "payement" =>  [
                    "amount" => 5000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 307,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 394,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 =>  [
                            "id" => "75140ebf-dd01-4f94-9a9d-17e3996d0ffa",
                            "amount" => 5000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                    
                        1 =>  [
                            "id" => "f872ecdb-da21-4090-b5ce-18fd95f0bc57",
                            "amount" => 5000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "f11c9084-78df-4b1c-b4df-1499f200917a",
                            "user_id" => 307,
                        
                            "user" =>  [
                                "id" => 307,
                                "name" => "EGLI ABLA BÉRÉNICE",
                                "email" => "egliberenice@gmail.com",
                                "phone_number" => "+22870915822"
                                
                            ]
                        ]
                    ]
                ]
            ],
            
            7 =>  [
                "user" => 312,
                "montant" => 10000.0,
                "payement" =>  [
                    "amount" => 10000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 312,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 395,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 =>  [
                            "id" => "16486f7c-62c3-466d-89ae-301a5f035a4d",
                            "amount" => 10000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                        
                        1 =>  [
                            "id" => "1858025c-3882-46c4-a69b-04f4212f4f06",
                            "amount" => 10000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "0f595033-e302-42c9-ade7-3e9ed0522fea",
                            "user_id" => 312,
                            
                            "user" =>  [
                                "id" => 312,
                                "name" => "ZANKLY Nicole Fabiola",
                                "email" => null,
                                "phone_number" => "+22892257659",
                                
                            ]
                        ]
                    ]
                ]
            ],
            
            8 =>  [
            "user" => 263,
            "montant" => 0.0,
            
            ],
            9 =>  [
            "user" => 377,
            "montant" => 0.0,
            
            ],
            10 =>  [
                "user" => 230,
                "montant" => 2000.0,
                "payement" =>  [
                    "amount" => 2000.0,
                    "description" => "payement done to user : 1 .  PRIMARY",
                    "payment_status_id" => 2,
                    "payment_method_id" => 11,
                    "user_id" => 230,
                    "updated_at" => "2026-01-16T11:18:19.000000Z",
                    "created_at" => "2026-01-16T11:18:19.000000Z",
                    "id" => 398,
                    "custom_fields" => [],
                    "transactions" =>  [
                        0 =>  [
                            "id" => "1dce92a5-11e9-4d11-b282-ab93f5cbcb2b",
                            "amount" => 2000.0,
                            "description" => "compte credité",
                            "action" => "credit",
                            "status" => "completed",
                            "wallet_id" => "01194a4f-f302-47af-80b2-ceb2075d36dc",
                            "user_id" => 1,
                            
                            "user" =>  [
                                "id" => 1,
                                "name" => "PRIMARY",
                                
                            ]
                        ],
                        
                    
                        1 =>  [
                            "id" => "941040f2-4b25-4e56-96ef-4b9bf9549e3a",
                            "amount" => 2000.0,
                            "description" => "compte débité",
                            "action" => "debit",
                            "status" => "completed",
                            "wallet_id" => "540ecc1f-f9b3-4041-94bb-6fc6fddb9bb6",
                            "user_id" => 230,
                            
                            "user" =>  [
                                "id" => 230,
                                "name" => "lovable",
                                "email" => "love@gmail.com",
                                "phone_number" => "+22897334363",
                                
                            ]
                        ]
                    ]
                ]
            ],
            
            
            11 =>  [
            "user" => 380,
            "montant" => 0.0,
            
            ]
        ] ;
    }

    
         //
}
