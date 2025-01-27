<?php
/*
 * File name: SalonLevelsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class SalonLevelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('salon_levels')->truncate();

        DB::table('salon_levels')->insert(array(
            0 =>
                array(
                    'id' => 2,
                    'name' => 'Level One',
                    'commission' => 50.0,
                    'disabled' => 0,
                    'default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 3,
                    'name' => 'Level Two',
                    'commission' => 75.0,
                    'disabled' => 0,
                    'default' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 4,
                    'name' => 'Level Three',
                    'commission' => 85.0,
                    'disabled' => 0,
                    'default' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
