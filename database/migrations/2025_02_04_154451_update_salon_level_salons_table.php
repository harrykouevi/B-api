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
        Schema::table('salons', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['salon_level_id']);
            
            // Change salon_level_id to be nullable
            $table->integer('salon_level_id')->unsigned()->nullable()->change();
            
            // Re-add the foreign key constraint with onDelete set to null
            $table->foreign('salon_level_id')->references('id')->on('salon_levels')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            // Drop the foreign key constraint in reverse migration
            $table->dropForeign(['salon_level_id']);
            
            // Change salon_level_id back to not nullable
            $table->integer('salon_level_id')->unsigned()->nullable(false)->change();
            
            // Re-add the original foreign key constraint
            $table->foreign('salon_level_id')->references('id')->on('salon_levels')->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
