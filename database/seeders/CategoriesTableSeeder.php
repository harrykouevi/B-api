<?php
/*
 * File name: CategoriesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('categories')->truncate();


        DB::table('categories')->insert(array(array(
            'id' => 1,
            'name' => 'Hair',
            'color' => '#ff9f43',
            'description' => '<p>Categories for all hair services</p>',
            'order' => 1,
            'featured' => 1,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 2,
            'name' => 'Nail',
            'color' => '#0abde3',
            'description' => '<p>Categories for all Medical Services<br></p>',
            'order' => 2,
            'featured' => 1,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 3,
            'name' => 'Skin',
            'color' => '#ee5253',
            'description' => '<p>Category for all Laundry Service</p>',
            'order' => 3,
            'featured' => 1,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 4,
            'name' => 'Eyebrows',
            'color' => '#10ac84',
            'description' => '<p>Category for Eyebrows</p>',
            'order' => 4,
            'featured' => 0,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 5,
            'name' => 'Massage',
            'color' => '#5f27cd',
            'description' => '<p>Category for Massage</p>',
            'order' => 5,
            'featured' => 0,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 6,
            'name' => 'Makeup',
            'color' => '#ff9f43',
            'description' => '<p>Category for Makeup</p>',
            'order' => 6,
            'featured' => 0,
            'parent_id' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 7,
            'name' => 'Spa',
            'color' => '#5f27cd',
            'description' => '<p>Category for Spa<br></p>',
            'order' => 1,
            'featured' => 0,
            'parent_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 8,
            'name' => 'Braid',
            'color' => '#5f27cd',
            'description' => '<p>Category for Braid<br></p>',
            'order' => 2,
            'featured' => 0,
            'parent_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 9,
            'name' => 'Tattoo',
            'color' => '#5f27cd',
            'description' => '<p>Category for Tattoo<br></p>',
            'order' => 1,
            'featured' => 0,
            'parent_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 10,
            'name' => 'Aesthetic Medicine',
            'color' => '#5f27cd',
            'description' => '<p>Category for Aesthetic Medicine<br></p>',
            'order' => 1,
            'featured' => 0,
            'parent_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 11,
            'name' => 'Piercing',
            'color' => '#5f27cd',
            'description' => '<p>Category for Piercing<br></p>',
            'order' => 1,
            'featured' => 0,
            'parent_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ), array(
            'id' => 12,
            'name' => 'Holistic Medicine',
            'color' => '#5f27cd',
            'description' => '<p>Category for Holistic Medicine<br></p>',
            'order' => 1,
            'featured' => 0,
            'parent_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ),
        ));


    }
}
