<?php
/*
 * File name: UserFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class UserFactory
 * @package Database\Factories
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail,
        ];
    }

    /**
     * Define the model's state for 'register'.
     *
     * @return Factory
     */
    public function stateRegister(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => fake()->name,
                'password' => '123456', // 123456
                'password_confirmation' => '123456', // 123456
            ];
        });

    }

    /**
     * Define the model's state for 'login'.
     *
     * @return Factory
     */
    public function stateLogin(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'password' => '123456', // 123456
            ];
        });
    }
}
