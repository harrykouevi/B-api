<?php
/*
 * File name: 2021_02_24_102838_create_custom_pages_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCustomPagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('custom_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('title')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('published')->nullable()->default(0);
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
        Schema::dropIfExists('custom_pages');
    }
}
