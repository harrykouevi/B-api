<?php
/*
 * File name: BookingsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Booking;
use DB;
use Illuminate\Database\Seeder;

class BookingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('bookings')->delete();
        DB::table('bookings')->truncate();
        Booking::factory()->count(20)->create();
    }
}
