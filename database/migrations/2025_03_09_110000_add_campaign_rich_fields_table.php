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
            $table->string('message_format', 20)->default('plain')->after('message');
            $table->string('image_url')->nullable()->after('message_format');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['message_format', 'image_url', 'failed_count']);
        });
    }
};
