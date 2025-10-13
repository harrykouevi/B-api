<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name');
            $table->longText('description');
            $table->double('price');
            $table->foreignId('service_template_id')->constrained('service_templates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_templates');
    }
};
