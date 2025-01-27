<?php
/*
 * File name: 2021_01_30_111717_create_salon_payouts_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalonPayoutsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salon_payouts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('salon_id')->unsigned();
            $table->string('method', 127);
            $table->double('amount', 10, 2)->default(0);
            $table->dateTime('paid_date');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('salon_payouts');
    }
}
