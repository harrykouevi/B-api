<?php
/*
 * File name: 2021_01_16_160838_create_availability_hours_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAvailabilityHoursTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('availability_hours', function (Blueprint $table) {
            $table->increments('id');
            $table->string('day', 16)->default('monday');
            $table->string('start_at', 16)->nullable();
            $table->string('end_at', 16)->nullable();
            $table->longText('data')->nullable();
            $table->integer('salon_id')->unsigned();
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_hours');
    }
}
