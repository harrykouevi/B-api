<?php
/*
 * File name: 2019_08_29_213926_create_faqs_table.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFaqsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('question')->nullable();
            $table->longText('answer')->nullable();
            $table->integer('faq_category_id')->unsigned();
            $table->timestamps();
            $table->foreign('faq_category_id')->references('id')->on('faq_categories')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
}
