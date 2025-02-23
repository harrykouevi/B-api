<?php
/*
 * File name: 2021_01_15_115850_create_salon_taxes_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalonTaxesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salon_taxes', function (Blueprint $table) {
            $table->integer('salon_id')->unsigned();
            $table->integer('tax_id')->unsigned();
            $table->primary(['salon_id', 'tax_id']);
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('salon_taxes');
    }
}
