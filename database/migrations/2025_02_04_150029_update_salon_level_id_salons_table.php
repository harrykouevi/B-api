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
            if (Schema::hasColumn('salons', 'email')) {
                $table->integer('salon_level_id')->unsigned()->nullable()->change();
                // $table->integer('salon_level_id')->nullable()->change();
            }
           
                
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('your_table_name', function (Blueprint $table) {
            $table->integer('salon_level_id')->nullable(false)->change();
        });
    }
};
