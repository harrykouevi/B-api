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
        Schema::create('paydunya_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('wallet_id');
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->unique();
            $table->string('status')->default('pending');
            $table->string('payment_channel')->nullable();
            $table->string('description')->nullable();
            $table->text('payment_url')->nullable();
            $table->json('payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paydunya_payment_requests');
    }
};
