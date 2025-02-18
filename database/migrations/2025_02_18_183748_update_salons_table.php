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
            $table->dropForeign(['address_id']);
            
            // Change salon_level_id to be nullable
            $table->integer('address_id')->unsigned()->nullable()->change();
            
            // Re-add the foreign key constraint with onDelete set to null
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            // Drop the foreign key constraint in reverse migration
            $table->dropForeign(['address_id']);
            
        });
    }
};
