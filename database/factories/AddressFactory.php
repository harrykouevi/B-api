<?php
/*
 * File name: AddressFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AddressFactory
 * @package Database\Factories
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Address::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->randomElement(['Work', 'Home', 'Office', 'Workshop', 'Building', 'Hotel']),
            'address' => fake()->address,
            'latitude' => fake()->randomFloat(8, 50, 52),
            'longitude' => fake()->randomFloat(8, 9, 12),
            'user_id' => User::all()->random()->id
        ];
    }

    /**
     * @return Factory
     */
    public function more_255_char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => fake()->paragraph(30),
                'address' => fake()->paragraph(30),
                'latitude' => 210,
                'longitude' => -203,
            ];
        });
    }

    public function empty(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'address' => null,
                'latitude' => null,
                'longitude' => null,
            ];
        });
    }
}
