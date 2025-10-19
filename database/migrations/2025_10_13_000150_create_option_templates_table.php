<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('option_templates');
        Schema::create('option_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('name');
            $table->longText('description');
            $table->double('price');
            $table->integer('option_group_id')->unsigned()->nullable();
            $table->foreign('option_group_id')->references('id')->on('option_groups')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('service_template_id')->constrained('service_templates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_templates');
    }
};
