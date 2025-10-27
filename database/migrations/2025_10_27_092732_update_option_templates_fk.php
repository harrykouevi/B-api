<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('option_templates', function (Blueprint $table) {
            $table->dropForeign(['service_template_id']);
            $table->foreign('service_template_id')
                  ->references('id')
                  ->on('service_templates')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('option_templates', function (Blueprint $table) {
            $table->dropForeign(['service_template_id']);
            $table->foreign('service_template_id')
                  ->references('id')
                  ->on('service_templates');
        });
    }
};
