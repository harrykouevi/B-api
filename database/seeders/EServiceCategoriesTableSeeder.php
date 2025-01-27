<?php
/*
 * File name: EServiceCategoriesTableSeeder.php
 * Last modified: 2024.04.11 at 14:17:29
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\EServiceCategory;
use DB;
use Illuminate\Database\Seeder;

class EServiceCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('e_service_categories')->truncate();

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }

        try {
            EServiceCategory::factory()->count(10)->create();
        } catch (\Exception) {
        }


    }
}
