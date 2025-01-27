<?php
/*
 * File name: 2021_01_07_164755_create_payment_statuses_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePaymentStatusesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('status')->nullable();
            $table->integer('order')->unsigned()->default(0);
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
        Schema::dropIfExists('payment_statuses');
    }
}
