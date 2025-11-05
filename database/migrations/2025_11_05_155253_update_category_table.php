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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->dropUnique(['name']);
            // Add the new composite unique constraint
            // $table->unique(['parent_id', 'name'], 'unique_parent_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('unique_parent_name');

            // Restore the unique constraint on 'name' only
            $table->unique('name');
        });
    }
};
