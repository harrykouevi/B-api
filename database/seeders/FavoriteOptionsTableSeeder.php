<?php
/*
 * File name: FavoriteOptionsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class FavoriteOptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('favorite_options')->truncate();
    }
}
