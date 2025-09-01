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
        Schema::table('bookings', function (Blueprint $table) {
            
            $table->integer('original_booking_id')->unsigned()->nullable()->after('cancel');
            $table->integer('reported_from_id')->unsigned()->nullable()->after('original_booking_id');
            $table->text('report_reason')->nullable()->after('reported_from_id');
            $table->text('cancellation_reason')->nullable()->after('report_reason');
            $table->string('cancelled_by')->nullable()->after('cancellation_reason');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->index('original_booking_id');
            $table->index('reported_from_id');


            $table->foreign('original_booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('reported_from_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            
            $table->dropForeign(['original_booking_id']);
            $table->dropForeign(['reported_from_id']);
            
            $table->dropIndex(['original_booking_id']);
            $table->dropIndex(['reported_from_id']);
            
            $table->dropColumn([
                'original_booking_id',
                'reported_from_id', 
                'report_reason',
                'cancellation_reason',
                'cancelled_by',
                'cancelled_at'
            ]);
        });
    }
};
