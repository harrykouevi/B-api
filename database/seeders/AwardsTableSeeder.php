<?php
/*
 * File name: AwardsTableSeeder.php
 * Last modified: 2024.04.11 at 13:59:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Award;
use DB;
use Illuminate\Database\Seeder;

class AwardsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('awards')->truncate();

        Award::factory()->count(50)->create();
    }
}
