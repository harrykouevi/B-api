<?php
/*
 * File name: CustomFieldsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class CustomFieldsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('custom_fields')->truncate();


        DB::table('custom_fields')->insert(array(
            0 =>
                array(
                    'id' => 5,
                    'name' => 'bio',
                    'type' => 'textarea',
                    'values' => NULL,
                    'disabled' => 0,
                    'required' => 0,
                    'in_table' => 0,
                    'bootstrap_column' => 6,
                    'order' => 1,
                    'custom_field_model' => 'App\\Models\\User',
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 6,
                    'name' => 'address',
                    'type' => 'text',
                    'values' => NULL,
                    'disabled' => 0,
                    'required' => 0,
                    'in_table' => 0,
                    'bootstrap_column' => 6,
                    'order' => 3,
                    'custom_field_model' => 'App\\Models\\User',
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
