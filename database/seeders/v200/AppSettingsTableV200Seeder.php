<?php
/*
 * File name: AppSettingsTableV200Seeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders\v200;

use DB;
use Illuminate\Database\Seeder;

class AppSettingsTableV200Seeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('app_settings')->insert(array(
            array(
                'key' => 'enable_otp',
                'value' => '1',
            ),
        ));
    }
}
