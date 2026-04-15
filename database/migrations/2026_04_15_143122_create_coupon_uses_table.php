<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('coupon_uses');

        Schema::create('coupon_uses', function (Blueprint $table) {
            $table->increments('id'); 
            // Relations
            
            $table->integer('coupon_id')->unsigned();
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade')->onUpdate('cascade');
         
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            

            // Option 1 (recommandé) : 1 ligne = 1 utilisation
            // => pas besoin de colonne number_of_use

            // Option 2 (si tu veux cumuler)
            // $table->unsignedInteger('number_of_use')->default(1);

            $table->timestamps();

            // Index important pour les perfs 🔥
            // $table->index(['coupon_id', 'user_id']);
            // Empêche doublon EXACT (optionnel selon ton choix)
            // $table->unique(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_uses');
    }
};