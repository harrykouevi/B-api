<?php
/*
 * File name: AddressesTableSeeder.php
 * Last modified: 2024.04.11 at 13:35:11
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Address;
use DB;
use Illuminate\Database\Seeder;

class AddressesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('addresses')->truncate();


        Address::factory()->count(20)->create();

    }
}
