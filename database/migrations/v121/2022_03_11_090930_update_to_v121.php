<?php
/*
 * File name: 2022_03_11_090930_update_to_v121.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class UpdateToV121 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::unprepared("UPDATE `media` SET `custom_properties` = REPLACE(`custom_properties`,',\"generated_conversions\":{\"thumb\":true,\"icon\":true}','') WHERE `media`.`model_type` = 'App\\Models\\Category' OR `media`.`model_type` = 'App\\Models\\Option'");

        if (Schema::hasTable('e_services')) {
            Schema::table('e_services', function (Blueprint $table) {
                $table->boolean('enable_at_customer_address')->after('enable_booking')->nullable()->default(1);
                $table->boolean('enable_at_salon')->after('enable_booking')->nullable()->default(1);
            });
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
