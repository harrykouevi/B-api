<?php
/*
 * File name: WalletTransactionFactory.php
 * Last modified: 2024.04.11 at 14:40:26
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => $this->faker->sentence(6),
            'amount' => $this->faker->randomNumber(),
            'user_id' => User::all()->random()->id,
            'action' => $this->faker->randomElement(['credit', 'debit']),
            'wallet_id' => Wallet::all()->random()->id,
        ];
    }
}
