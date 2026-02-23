<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('comments', function (Blueprint $table) {
        // 1. Pour le masquage (Shadowban/Masquer)
        $table->boolean('is_hidden')->default(false)->after('content');
        
        // 2. Pour la suppression réversible (Soft Delete)
        $table->softDeletes()->after('updated_at');
    });
}

public function down()
{
    Schema::table('comments', function (Blueprint $table) {
        $table->dropColumn('is_hidden');
        $table->dropSoftDeletes();
    });
}
};
