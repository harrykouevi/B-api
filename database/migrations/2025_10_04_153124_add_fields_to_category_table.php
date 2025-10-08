<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
            $table->longText('path')->nullable()->after('parent_id');
            $table->longText('path_slugs')->nullable()->after('path');
            $table->longText('path_names')->nullable()->after('path_slugs');

            // Index pour performance
            $table->index('slug');
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['path']);
            $table->dropColumn([
                'slug',
                'path',
                'path_slugs',
                'path_names'
            ]);
        });
    }
};