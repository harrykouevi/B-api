<?php
/*
 * File name: 2021_01_15_115747_create_salon_users_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalonUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('salon_users', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned();
            $table->integer('salon_id')->unsigned();
            $table->primary(['user_id', 'salon_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('salon_users');
    }
}
