<?php
/*
 * File name: 2021_01_25_222252_create_salon_reviews_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalonReviewsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salon_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->text('review')->nullable();
            $table->decimal('rate', 3, 2)->default(0);
            $table->integer('booking_id')->unsigned();
            $table->timestamps();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('salon_reviews');
    }
}
