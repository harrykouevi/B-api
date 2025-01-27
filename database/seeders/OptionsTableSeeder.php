<?php
/*
 * File name: OptionsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Option;
use DB;
use Illuminate\Database\Seeder;

class OptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('options')->truncate();
        Option::factory()->count(100)->create();
    }
}
