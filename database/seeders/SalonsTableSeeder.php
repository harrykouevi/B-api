<?php
/*
 * File name: SalonsTableSeeder.php
 * Last modified: 2024.04.11 at 14:17:29
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\SalonTax;
use App\Models\SalonUser;
use DB;
use Illuminate\Database\Seeder;

class SalonsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('salons')->truncate();

        Salon::factory()->count(10)->create();
        try {
            SalonUser::factory()->count(10)->create();
        } catch (\Exception) {
        }
        try {
            SalonUser::factory()->count(10)->create();
        } catch (\Exception) {
        }
        try {
            SalonUser::factory()->count(10)->create();
        } catch (\Exception) {
        }
        try {
            SalonTax::factory()->count(10)->create();
        } catch (\Exception) {
        }
        try {
            SalonTax::factory()->count(10)->create();
        } catch (\Exception) {
        }
        try {
            SalonTax::factory()->count(10)->create();
        } catch (\Exception) {
        }

    }
}
