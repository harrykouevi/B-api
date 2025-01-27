<?php
/*
 * File name: ExperiencesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Experience;
use DB;
use Illuminate\Database\Seeder;

class ExperiencesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('experiences')->truncate();

        Experience::factory()->count(50)->create();
    }
}
