<?php
/*
 * File name: PaymentMethodsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class PaymentMethodsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('payment_methods')->truncate();

        DB::table('payment_methods')->insert(array(
            array(
                'id' => 2,
                'name' => 'RazorPay',
                'description' => 'Click to pay with RazorPay gateway',
                'route' => '/RazorPay',
                'order' => 2,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 5,
                'name' => 'PayPal',
                'description' => 'Click to pay with your PayPal account',
                'route' => '/PayPal',
                'order' => 1,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 6,
                'name' => 'Cash',
                'description' => 'Click to pay cash when finish',
                'route' => '/Cash',
                'order' => 3,
                'default' => 1,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 7,
                'name' => 'Credit Card (Stripe)',
                'description' => 'Click to pay with your Credit Card',
                'route' => '/Stripe',
                'order' => 3,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 8,
                'name' => 'PayStack',
                'description' => 'Click to pay with PayStack gateway',
                'route' => '/PayStack',
                'order' => 5,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ), array(
                'id' => 9,
                'name' => 'FlutterWave',
                'description' => 'Click to pay with FlutterWave gateway',
                'route' => '/FlutterWave',
                'order' => 6,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 10,
                'name' => 'Malaysian Stripe FPX	',
                'description' => 'Click to pay with Stripe FPX gateway',
                'route' => '/StripeFPX',
                'order' => 7,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 11,
                'name' => 'Wallet',
                'description' => 'Click to pay with Wallet',
                'route' => '/Wallet',
                'order' => 8,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
            array(
                'id' => 12,
                'name' => 'PayMongo',
                'description' => 'Click to pay with PayMongo',
                'route' => '/PayMongo',
                'order' => 12,
                'default' => 0,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
        ));


    }
}
