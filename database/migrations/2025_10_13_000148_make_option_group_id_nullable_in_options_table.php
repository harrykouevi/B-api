<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->integer('option_group_id')->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->integer('option_group_id')->unsigned()->change();
        });
    }
};