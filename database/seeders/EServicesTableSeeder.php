<?php
/*
 * File name: EServicesTableSeeder.php
 * Last modified: 2024.04.11 at 14:17:29
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\EService;
use DB;
use Illuminate\Database\Seeder;

class EServicesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('e_services')->truncate();

        EService::factory()->count(40)->create();


    }
}
