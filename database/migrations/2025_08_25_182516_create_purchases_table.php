<?php

/*
 * File name: 2025_08_25_182513_create_purchases_table.php
 * Last modified: 2025.08.25 at 16:53:52
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('salon');
            $table->longText('e_services');
            $table->longText('booking')->nullable();
            $table->smallInteger('quantity')->nullable()->default(1);
            $table->bigInteger('user_id')->nullable()->unsigned();
            $table->integer('purchase_status_id')->nullable()->unsigned();
            $table->integer('payment_id')->nullable()->unsigned();
            $table->longText('coupon')->nullable();
            $table->longText('taxes')->nullable();
            $table->dateTime('purchase_at')->nullable();
            $table->text('hint')->nullable();
            $table->boolean('cancel')->nullable()->default(0);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('set null');
            $table->foreign('purchase_status_id')->references('id')->on('purchase_statuses')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null')->onUpdate('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        
        Schema::dropIfExists('purchases');
    }
};
