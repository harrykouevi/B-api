<?php
/*
 * File name: CustomFieldValuesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class CustomFieldValuesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('custom_field_values')->truncate();


        DB::table('custom_field_values')->insert(array(
            0 =>
                array(
                    'id' => 30,
                    'value' => 'Explicabo. Eum provi.&nbsp;',
                    'view' => 'Explicabo. Eum provi.&nbsp;',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            1 =>
                array(
                    'id' => 31,
                    'value' => 'Modi est libero qui',
                    'view' => 'Modi est libero qui',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            2 =>
                array(
                    'id' => 33,
                    'value' => 'Consequatur error ip.&nbsp;',
                    'view' => 'Consequatur error ip.&nbsp;',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            3 =>
                array(
                    'id' => 34,
                    'value' => 'Qui vero ratione vel',
                    'view' => 'Qui vero ratione vel',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            4 =>
                array(
                    'id' => 36,
                    'value' => 'Dolor optio, error e',
                    'view' => 'Dolor optio, error e',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            5 =>
                array(
                    'id' => 37,
                    'value' => 'Voluptatibus ad ipsu',
                    'view' => 'Voluptatibus ad ipsu',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            6 =>
                array(
                    'id' => 39,
                    'value' => 'Faucibus ornare suspendisse sed nisi lacus sed. Pellentesque sit amet porttitor eget dolor morbi non arcu. Eu scelerisque felis imperdiet proin fermentum leo vel orci porta',
                    'view' => 'Faucibus ornare suspendisse sed nisi lacus sed. Pellentesque sit amet porttitor eget dolor morbi non arcu. Eu scelerisque felis imperdiet proin fermentum leo vel orci porta',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            7 =>
                array(
                    'id' => 40,
                    'value' => 'Sequi molestiae ipsa1',
                    'view' => 'Sequi molestiae ipsa1',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            8 =>
                array(
                    'id' => 42,
                    'value' => 'Omnis fugiat et cons.',
                    'view' => 'Omnis fugiat et cons.',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            9 =>
                array(
                    'id' => 43,
                    'value' => 'Consequatur delenit',
                    'view' => 'Consequatur delenit',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            10 =>
                array(
                    'id' => 45,
                    'value' => '<p>Short bio for this driver</p>',
                    'view' => 'Short bio for this driver',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 6,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            11 =>
                array(
                    'id' => 46,
                    'value' => '4722 Villa Drive',
                    'view' => '4722 Villa Drive',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 6,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            12 =>
                array(
                    'id' => 48,
                    'value' => 'Voluptatem. Omnis op.',
                    'view' => 'Voluptatem. Omnis op.',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 7,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            13 =>
                array(
                    'id' => 49,
                    'value' => 'Perspiciatis aut ei',
                    'view' => 'Perspiciatis aut ei',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 7,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            14 =>
                array(
                    'id' => 51,
                    'value' => 'sdfsdf56',
                    'view' => 'sdfsdf56',
                    'custom_field_id' => 5,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 8,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
            15 =>
                array(
                    'id' => 52,
                    'value' => 'Adressttt',
                    'view' => 'Adressttt',
                    'custom_field_id' => 6,
                    'customizable_type' => 'App\\Models\\User',
                    'customizable_id' => 8,
                    'created_at' => now(),
                    'updated_at' => now(),
                ),
        ));


    }
}
