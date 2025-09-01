<?php

/*
 * File name: 2025_08_25_182433_create_purchase_statuses_table.php
 * Last modified: 2025.08.25 at 16:53:52
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('purchase_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('status')->nullable();
            $table->tinyInteger('order')->unsigned()->default(0);
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
        Schema::dropIfExists('purchase_statuses');
    }
};
