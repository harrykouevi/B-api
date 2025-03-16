<?php
/*
 * File name: CurrenciesTableSeeder.php
 * Last modified: 2024.04.11 at 13:59:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('currencies')->truncate();


        DB::table('currencies')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'name' => 'FCFA',
                    'symbol' => 'XOF',
                    'code' => 'XOF',
                    'decimal_digits' => 0,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
           
        ));


    }
}
