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
        Schema::table('e_services', function (Blueprint $table) {
            
           $table->index('slug', 'id_e_service_slug');
        });

        Schema::table('service_templates', function (Blueprint $table) {
            
           $table->index('slug', 'id_service_template_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('id_e_service_slug'); 
        });

        Schema::table('service_templates', function (Blueprint $table) {
            $table->dropIndex('id_service_template_slug'); 
        });
    }
};
