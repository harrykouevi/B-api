<?php
/*
 * File name: RolesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

// use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        // Clear pivots first to avoid FK constraint errors.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('roles')->insert(array(
            0 =>
                array(
                    'id' => 1,
                    'name' => 'admin',
                    'guard_name' => 'web',
                    'default' => 0,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
            1 =>
                array(
                    'id' => 2,
                    'name' => 'salon owner',
                    'guard_name' => 'web',
                    'default' => 0,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
            2 =>
                array(
                    'id' => 3,
                    'name' => 'customer',
                    'guard_name' => 'web',
                    'default' => 1,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ),
        ));


    }
}
