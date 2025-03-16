<?php
/*
 * File name: WalletsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class WalletsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('wallets')->truncate();
        DB::table('wallets')->insert(array(
            array(
                'id' => '01194a4f-f302-47af-80b2-ceb2075d36dc',
                'name' => 'My USD Wallet',
                'balance' => 5000000,
                'currency' => '{"id":1,"name":"FCFA","symbol":"XOF","code":"XOF","decimal_digits":0,"rounding":0}',
                'user_id' => 1,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
               
            ),
           
        ));

    }
}
