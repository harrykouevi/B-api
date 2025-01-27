<?php
/*
 * File name: 2021_01_13_105605_create_salon_levels_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalonLevelsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salon_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name')->nullable();
            $table->double('commission', 5, 2)->default(0);
            $table->boolean('disabled')->default(0);
            $table->boolean('default')->default(0);
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
        Schema::dropIfExists('salon_levels');
    }
}
