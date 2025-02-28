<?php
/*
 * File name: FaqCategoriesTableSeeder.php
 * Last modified: 2024.04.11 at 14:17:29
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\FaqCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class FaqCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('faq_categories')->truncate();

        FaqCategory::factory()->count(5)->create();

    }
}
