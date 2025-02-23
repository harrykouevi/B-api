<?php
/*
 * File name: 2021_01_30_135356_create_earnings_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEarningsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('earnings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('salon_id')->nullable()->unsigned();
            $table->integer('total_bookings')->unsigned()->default(0);
            $table->double('total_earning', 10, 2)->default(0);
            $table->double('admin_earning', 10, 2)->default(0);
            $table->double('salon_earning', 10, 2)->default(0);
            $table->double('taxes', 10, 2)->default(0);
            $table->timestamps();
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('set null')->onUpdate('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
}
