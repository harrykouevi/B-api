<?php
/*
 * File name: 2021_01_13_114302_create_taxes_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaxesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name')->nullable();
            $table->double('value', 10, 2)->default(0);
            $table->string('type', 50);
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
        Schema::dropIfExists('taxes');
    }
}
