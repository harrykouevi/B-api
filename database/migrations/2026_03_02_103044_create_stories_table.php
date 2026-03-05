<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('stories', function (Blueprint $table) {
        $table->id();
        // L'auteur de la story
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        // Le contenu
        $table->string('media_path'); // URL de l'image ou vidéo
        $table->enum('type', ['image', 'video'])->default('image');
        
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
