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
        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'commission', 'penalty', 'booking_fee', 'postpone_fee'
            $table->double('amount', 10, 2); // Montant gagné (peut être négatif si remboursement)
            $table->unsignedInteger('booking_id')->nullable();
            $table->unsignedInteger('salon_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();  // BIGINT pour correspondre à users.id
            $table->text('description'); // Description détaillée
            $table->timestamps();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');

            // Index pour les requêtes fréquentes
            $table->index('type');
            $table->index('booking_id');
            $table->index('salon_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
    }
};
