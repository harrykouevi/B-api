<?php
/*
 * File name: OptionGroupsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class OptionGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('option_groups')->truncate();

        DB::table('option_groups')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'name' => 'Size',
                    'allow_multiple' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 2,
                    'name' => 'Color',
                    'allow_multiple' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 3,
                    'name' => 'Type',
                    'allow_multiple' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
