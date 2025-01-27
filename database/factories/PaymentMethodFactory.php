<?php
/*
 * File name: PaymentMethodFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PaymentMethodFactory
 * @package Database\Factories
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->text(48),
            'description' => fake()->sentence(5),
            'route' => fake()->randomElement(['/PayPal', '/RazorPay', '/CashOnDelivery', '/Strip']),
            'order' => fake()->numberBetween(1, 10),
            'default' => fake()->boolean(),
            'enabled' => fake()->boolean(),
        ];
    }

    /**
     * Define the model's state for 'name_more_127_char'.
     *
     * @return Factory
     */
    public function stateNameMore127Char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => fake()->paragraph(20),
            ];
        });
    }

    /**
     * Define the model's state for 'description_more_127_char'.
     *
     * @return Factory
     */
    public function stateDescriptionMore127Char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => fake()->paragraph(20),
            ];
        });
    }

    /**
     * Define the model's state for 'route_more_127_char'.
     *
     * @return Factory
     */
    public function stateRouteMore127Char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'route' => fake()->paragraph(20),
            ];
        });
    }

    /**
     * Define the model's state for 'order_negative'.
     *
     * @return Factory
     */
    public function stateOrderNegative(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'order' => -1,
            ];
        });
    }
}
