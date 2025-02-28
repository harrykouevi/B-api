<?php
/*
 * File name: PaymentStatusesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PaymentStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('payment_statuses')->truncate();

        DB::table('payment_statuses')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'status' => 'Pending',
                    'order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 2,
                    'status' => 'Paid',
                    'order' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 3,
                    'status' => 'Failed',
                    'order' => 20,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            3 =>
                array(
                    'id' => 4,
                    'status' => 'Refunded',
                    'order' => 40,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
