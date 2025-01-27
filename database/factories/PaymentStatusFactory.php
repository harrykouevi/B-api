<?php
/*
 * File name: PaymentStatusFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PaymentStatusFactory
 * @package Database\Factories
 */
class PaymentStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'status' => fake()->text(48),
            'order' => fake()->numberBetween(1, 10)
        ];
    }

    /**
     * Define the model's state for 'status_more_127_char'.
     *
     * @return Factory
     */
    public function stateStatusMore127Char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => fake()->paragraph(20),
            ];
        });
    }
}
