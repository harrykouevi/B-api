<?php
/*
 * File name: CurrenciesTableSeeder.php
 * Last modified: 2024.04.11 at 13:59:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use DB;
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
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'code' => 'USD',
                    'decimal_digits' => 2,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 2,
                    'name' => 'Euro',
                    'symbol' => '€',
                    'code' => 'EUR',
                    'decimal_digits' => 2,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 3,
                    'name' => 'Indian Rupee',
                    'symbol' => 'টকা',
                    'code' => 'INR',
                    'decimal_digits' => 2,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            3 =>
                array(
                    'id' => 4,
                    'name' => 'Indonesian Rupiah',
                    'symbol' => 'Rp',
                    'code' => 'IDR',
                    'decimal_digits' => 0,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            4 =>
                array(
                    'id' => 5,
                    'name' => 'Brazilian Real',
                    'symbol' => 'R$',
                    'code' => 'BRL',
                    'decimal_digits' => 2,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            5 =>
                array(
                    'id' => 6,
                    'name' => 'Cambodian Riel',
                    'symbol' => '៛',
                    'code' => 'KHR',
                    'decimal_digits' => 2,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            6 =>
                array(
                    'id' => 7,
                    'name' => 'Vietnamese Dong',
                    'symbol' => '₫',
                    'code' => 'VND',
                    'decimal_digits' => 0,
                    'rounding' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
