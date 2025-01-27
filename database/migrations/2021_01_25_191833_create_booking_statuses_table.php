<?php
/*
 * File name: 2021_01_25_191833_create_booking_statuses_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBookingStatusesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('booking_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('status')->nullable();
            $table->tinyInteger('order')->unsigned()->default(0);
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
        Schema::dropIfExists('booking_statuses');
    }
}
