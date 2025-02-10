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
        Schema::dropIfExists('affiliates');
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('link')->unique();

             // Change salon_level_id to be nullable
             $table->bigInteger('user_id')->unsigned()->nullable();
             // Re-add the foreign key constraint with onDelete set to null
             $table->foreign('user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
         
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};