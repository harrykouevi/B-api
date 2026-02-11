<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sécurité : on nettoie si une table incomplète existe déjà
        Schema::dropIfExists('likes');

        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            // 1. L'Utilisateur (BigInt par défaut dans Laravel)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 2. Le Post (On utilise 'integer' car c'est ce que tes autres tables utilisent)
            $table->integer('post_id')->unsigned(); 
            
            // 3. La liaison explicite vers la table posts
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');

            // 4. Empêcher les doublons (1 seul like par user par post)
            $table->unique(['user_id', 'post_id']); 

            // 5. La date du like (created_at) demandée sur ton schéma
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};