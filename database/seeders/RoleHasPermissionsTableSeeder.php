<?php
/*
 * File name: RoleHasPermissionsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RoleHasPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        DB::table('role_has_permissions')->truncate();

        DB::table('role_has_permissions')->insert(array(

            array(
                'permission_id' => 1,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 2,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 2,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 2,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 3,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 3,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 3,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 4,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 5,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 6,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 7,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 8,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 9,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 9,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 9,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 10,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 11,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 11,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 11,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 12,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 12,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 13,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 16,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 19,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 20,
                'role_id' => 1,
            ),

            // array(
            //     'permission_id' => 27,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 28,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 29,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 30,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 31,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 32,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 33,
            //     'role_id' => 1,
            // ),

            array(
                'permission_id' => 34,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 35,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 36,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 37,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 38,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 39,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 40,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 41,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 42,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 42,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 42,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 43,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 44,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 45,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 46,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 47,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 48,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 48,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 48,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 49,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 50,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 51,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 52,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 53,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 54,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 54,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 54,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 57,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 57,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 58,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 59,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 60,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 61,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 62,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 63,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 66,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 67,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 69,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 70,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 71,
                'role_id' => 1,
            ),

            // array(
            //     'permission_id' => 72,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 72,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 73,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 73,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 74,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 74,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 75,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 75,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 76,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 76,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 77,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 77,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 78,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 78,
            //     'role_id' => 2,
            // ),

            array(
                'permission_id' => 79,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 79,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 80,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 80,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 81,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 81,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 82,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 82,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 83,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 83,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 84,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 84,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 85,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 85,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 92,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 92,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 93,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 94,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 95,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 96,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 97,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 98,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 98,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 98,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 99,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 99,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 99,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 100,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 100,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 100,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 101,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 101,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 102,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 102,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 103,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 104,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 104,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 104,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 105,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 105,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 105,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 106,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 106,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 106,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 107,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 107,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 107,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 108,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 108,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 108,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 109,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 109,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 109,
                'role_id' => 3,
            ),

            // array(
            //     'permission_id' => 110,
            //     'role_id' => 1,
            // ),

            array(
                'permission_id' => 111,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 112,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 113,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 114,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 115,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 116,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 116,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 117,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 117,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 118,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 118,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 119,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 119,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 120,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 120,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 121,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 121,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 122,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 122,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 122,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 123,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 123,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 124,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 124,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 126,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 126,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 127,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 127,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 128,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 128,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 129,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 129,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 129,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 130,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 131,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 132,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 133,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 134,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 135,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 135,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 136,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 136,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 137,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 137,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 138,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 139,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 140,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 141,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 141,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 142,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 142,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 143,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 143,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 144,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 144,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 145,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 145,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 146,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 146,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 147,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 147,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 147,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 148,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 149,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 149,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 150,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 151,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 151,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 152,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 153,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 153,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 153,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 156,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 156,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 156,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 157,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 157,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 157,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 158,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 160,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 160,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 161,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 161,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 162,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 162,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 163,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 163,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 164,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 164,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 165,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 165,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 166,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 166,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 166,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 167,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 168,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 169,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 170,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 171,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 172,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 173,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 173,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 173,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 174,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 175,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 175,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 175,
                'role_id' => 3,
            ),

            // array(
            //     'permission_id' => 176,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 176,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 177,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 178,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 179,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 179,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 180,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 180,
            //     'role_id' => 2,
            // ),

            array(
                'permission_id' => 181,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 182,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 182,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 185,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 186,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 188,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 188,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 188,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 191,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 191,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 191,
                'role_id' => 3,
            ),

            array(
                'permission_id' => 192,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 192,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 193,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 193,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 194,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 194,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 195,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 195,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 196,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 196,
                'role_id' => 2,
            ),

            array(
                'permission_id' => 197,
                'role_id' => 1,
            ),

            array(
                'permission_id' => 197,
                'role_id' => 2,
            ),

            // array(
            //     'permission_id' => 199,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 199,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 200,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 200,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 203,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 203,
            //     'role_id' => 2,
            // ),

            // array(
            //     'permission_id' => 203,
            //     'role_id' => 3,
            // ),

            // array(
            //     'permission_id' => 204,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 205,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 206,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 207,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 208,
            //     'role_id' => 1,
            // ),

            // array(
            //     'permission_id' => 209,
            //     'role_id' => 1,
            // ),
            array(
                'permission_id' => 210,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 211,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 212,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 213,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 214,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 215,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 216,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 217,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 218,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 216,
                'role_id' => 2,
            ),
            array(
                'permission_id' => 210,
                'role_id' => 2,
            ),
            array(
                'permission_id' => 216,
                'role_id' => 3,
            ),
            array(
                'permission_id' => 210,
                'role_id' => 3,
            ),
            array(
                'permission_id' => 219,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 220,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 221,
                'role_id' => 1,
            ),
            array(
                'permission_id' => 222,
                'role_id' => 1,
            ),
        ));


    }
}