<?php
/*
 * File name: 2021_01_22_194514_create_option_groups_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOptionGroupsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name')->nullable();
            $table->boolean('allow_multiple')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('option_groups');
    }
}
