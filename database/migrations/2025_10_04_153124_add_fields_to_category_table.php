<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'slug')){ 
                $table->string('slug')->unique()->after('name');
                $table->index('slug');
            }
            if (!Schema::hasColumn('categories', 'path')){ 
                $table->index('path');
                $table->longText('path')->nullable()->after('parent_id');
            }
            if (!Schema::hasColumn('categories', 'path_slugs')) $table->longText('path_slugs')->nullable()->after('path');
            if (!Schema::hasColumn('categories', 'path_names')) $table->longText('path_names')->nullable()->after('path_slugs');

            
            
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