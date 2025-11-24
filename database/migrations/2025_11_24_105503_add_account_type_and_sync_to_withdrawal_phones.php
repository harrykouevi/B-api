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
        Schema::table('withdrawal_phones', function (Blueprint $table) {
            $table->enum('account_type', ['yas', 'moov'])->nullable()->after('phone_number');
            $table->boolean('is_sync_cinetpay')->default(false)->after('account_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_phones', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'is_sync_cinetpay']);
        });
    }
};
