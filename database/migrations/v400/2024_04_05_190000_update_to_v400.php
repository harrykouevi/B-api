<?php
/*
 * File name: 2024_04_05_190000_update_to_v400.php
 * Last modified: 2024.04.09 at 07:54:12
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateToV400 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('media') && !Schema::hasColumn('media','conversions_disk')) {
            Schema::table('media', function (Blueprint $table) {
                $table->uuid()->after('id')->nullable()->unique();
                $table->string('conversions_disk')->after('disk')->nullable()->default('public');
                $table->json('responsive_images')->after('generated_conversions');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {

    }
}
