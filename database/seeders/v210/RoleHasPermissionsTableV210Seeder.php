<?php
/*
 * File name: RoleHasPermissionsTableV210Seeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders\v210;

use DB;
use Illuminate\Database\Seeder;

class RoleHasPermissionsTableV210Seeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        if (DB::table('role_has_permissions')->where('permission_id', '=', 219)->count() == 0) {
            DB::table('role_has_permissions')->insert(array(
                array(
                    'permission_id' => 219,
                    'role_id' => 2,
                ),
                array(
                    'permission_id' => 220,
                    'role_id' => 2,
                ),
                array(
                    'permission_id' => 221,
                    'role_id' => 2,
                ),
                array(
                    'permission_id' => 222,
                    'role_id' => 2,
                ),
            ));
        }


    }
}
