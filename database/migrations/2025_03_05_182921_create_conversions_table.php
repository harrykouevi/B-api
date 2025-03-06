<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->longText('affiliation')->nullable();
            $table->unsignedBigInteger('affiliate_id');
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('affiliates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
