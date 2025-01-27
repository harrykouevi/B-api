<?php
/*
 * File name: 2021_06_26_110636_create_json_unquote_function.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;

class CreateJsonUnquoteFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        try {
            DB::unprepared('
            CREATE FUNCTION `json_unquote`(`mdata` TEXT CHARSET utf8mb4) RETURNS text CHARSET utf8mb4
            BEGIN
            RETURN mdata;
            END');
        } catch (Exception $exception) {

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
