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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('action_type', 30)->default('home')->after('image_url');
            $table->string('deep_link', 2048)->nullable()->after('action_type');
            $table->string('cta_text', 120)->nullable()->after('deep_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['action_type', 'deep_link', 'cta_text']);
        });
    }
};
