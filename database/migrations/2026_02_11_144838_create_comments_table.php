<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
  public function up()
{
    Schema::dropIfExists('comments');

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id'); 
        $table->text('content');

        // On crée les colonnes normalement
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('post_id');

        /** * On NE met PAS les lignes $table->foreign(...)->references(...)
         * Cela évite l'erreur SQL 150 tout en permettant de stocker les IDs.
         */

        $table->timestamps();
    });
}

    public function down()
    {
        Schema::dropIfExists('comments');
    }
}