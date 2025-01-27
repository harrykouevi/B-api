<?php
/*
 * File name: RolesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('roles')->truncate();

        DB::table('roles')->insert(array(
            0 =>
                array(
                    'id' => 2,
                    'name' => 'admin',
                    'guard_name' => 'web',
                    'default' => 0,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
            1 =>
                array(
                    'id' => 3,
                    'name' => 'salon owner',
                    'guard_name' => 'web',
                    'default' => 0,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
            2 =>
                array(
                    'id' => 4,
                    'name' => 'customer',
                    'guard_name' => 'web',
                    'default' => 1,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
        ));


    }
}
