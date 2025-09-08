<?php
/*
 * File name: PurchaseStatusesTableSeeder.php
 * Last modified: 2025.08.25 at 16:53:52
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PurchaseStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        // DB::table('purchase_statuses')->truncate();


        DB::table('purchase_statuses')->insert(array(
           
            1 =>
                array(
                    'id' => 1,
                    'status' => 'Pending',
                    'order' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
           
            2 =>
                array(
                    'id' => 2,
                    'status' => 'Done',
                    'order' => 50,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            3 =>    array(
                    'id' => 3,
                    'status' => 'Canceled',
                    'order' => 90,
                    'created_at' => now(),
                    'updated_at' => now(),
                )
        ));


    }
}
