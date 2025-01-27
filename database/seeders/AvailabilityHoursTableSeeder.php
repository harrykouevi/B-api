<?php
/*
 * File name: AvailabilityHoursTableSeeder.php
 * Last modified: 2024.04.11 at 13:35:11
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\AvailabilityHour;
use DB;
use Illuminate\Database\Seeder;

class AvailabilityHoursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('availability_hours')->truncate();


        AvailabilityHour::factory()->count(50)->create();    }
}
