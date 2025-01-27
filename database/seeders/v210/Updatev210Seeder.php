<?php
/*
 * File name: Updatev210Seeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders\v210;

use Illuminate\Database\Seeder;

class Updatev210Seeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(PermissionsTableV210Seeder::class);
        $this->call(RoleHasPermissionsTableV210Seeder::class);
    }
}
