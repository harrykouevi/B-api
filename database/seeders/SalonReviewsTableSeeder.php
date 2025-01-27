<?php
/*
 * File name: SalonReviewsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\SalonReview;
use DB;
use Illuminate\Database\Seeder;

class SalonReviewsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('salon_reviews')->truncate();


        SalonReview::factory()->count(10)->create();

    }
}
