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
        
        Schema::table('users', function (Blueprint $table) {
            
            $table->timestamp('sponsorship_at')->nullable();
            $table->longText('sponsorship')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
