<?php
/*
 * File name: 2021_01_22_205819_create_favorites_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFavoritesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('e_service_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->foreign('e_service_id')->references('id')->on('e_services')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
}
