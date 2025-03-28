<?php
/*
 * File name: 2021_08_08_155732_create_wallet_transactions_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWalletTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id');
            $table->double('amount', 10, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->enum('action', ['credit', 'debit']);
            $table->uuid('wallet_id');
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->primary(['id']);
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
}
