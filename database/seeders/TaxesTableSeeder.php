<?php
/*
 * File name: TaxesTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Tax;
use DB;
use Illuminate\Database\Seeder;

class TaxesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('taxes')->truncate();

        Tax::factory()->count(10)->create();


    }
}
