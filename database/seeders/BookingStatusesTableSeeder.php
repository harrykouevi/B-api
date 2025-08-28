<?php
/*
 * File name: BookingStatusesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BookingStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('booking_statuses')->truncate();


        DB::table('booking_statuses')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'status' => 'Received',
                    'order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 2,
                    'status' => 'In Progress',
                    'order' => 40,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 3,
                    'status' => 'On the Way',
                    'order' => 20,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            3 =>
                array(
                    'id' => 4,
                    'status' => 'Accepted',
                    'order' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            4 =>
                array(
                    'id' => 5,
                    'status' => 'Ready',
                    'order' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            5 =>
                array(
                    'id' => 6,
                    'status' => 'Done',
                    'order' => 50,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            6 =>
                array(
                    'id' => 7,
                    'status' => 'Failed',
                    'order' => 60,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            7 =>
                array(
                    'id' => 8,
                    'status' => 'Cancelled',
                    'order' => 70,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            8 =>
                array(
                    'id' => 9,
                    'status' => 'Reported',
                    'order' => 80,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
