<?php
/*
 * File name: 2021_01_13_111155_create_salons_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateSalonsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name')->nullable();
            $table->integer('salon_level_id')->unsigned();
            $table->integer('address_id')->unsigned();
            $table->longText('description')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->double('availability_range', 9, 2)->nullable()->default(0);
            $table->boolean('available')->nullable()->default(1);
            $table->boolean('featured')->nullable()->default(0);
            $table->boolean('accepted')->nullable()->default(0);
            $table->timestamps();
            $table->foreign('salon_level_id')->references('id')->on('salon_levels')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('salons');
    }
}
