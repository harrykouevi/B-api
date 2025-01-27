<?php
/*
 * File name: WalletFactory.php
 * Last modified: 2024.04.11 at 14:31:13
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'balance' => $this->faker->randomNumber(),
            'currency' => $this->faker->sentence(6),
            'user_id' => $this->faker->randomNumber(),
            'enabled' => $this->faker->boolean,
        ];
    }
}
