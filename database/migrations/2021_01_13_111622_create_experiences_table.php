<?php
/*
 * File name: 2021_01_13_111622_create_experiences_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExperiencesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('title')->nullable();
            $table->longText('description')->nullable();
            $table->integer('salon_id')->unsigned();
            $table->timestamps();
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
        Schema::dropIfExists('experiences');
    }
}
