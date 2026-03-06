<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    Schema::dropIfExists('stories');
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    Schema::create('stories', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->bigInteger('user_id')->nullable()->unsigned();
        $table->integer('salon_id')->nullable()->unsigned();



        // L'auteur de la story
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        $table->foreign('salon_id')->references('id')->on('salons')->onDelete('cascade')->onUpdate('cascade');

        
        // La gestion du temps (Le coeur de la story)
        $table->timestamp('expires_at'); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
