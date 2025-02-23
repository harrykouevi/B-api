<?php
/*
 * File name: 2018_07_24_211327_create_custom_field_values_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCustomFieldValuesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('value')->nullable();
            $table->longText('view')->nullable();
            $table->integer('custom_field_id')->unsigned();
            $table->string('customizable_type', 127);
            $table->integer('customizable_id');
            $table->timestamps();
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
}
