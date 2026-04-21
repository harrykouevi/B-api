<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('app_settings')
            ->where('key', 'otp_sms_provider')
            ->exists();

        if (!$exists) {
            DB::table('app_settings')->insert([
                'key' => 'otp_sms_provider',
                'value' => 'termii',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->where('key', 'otp_sms_provider')
            ->delete();
    }
};
