<?php
/*
 * File name: SalonLevelFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\SalonLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SalonLevelFactory
 * @package Database\Factories
 */
class SalonLevelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalonLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->text(48),
            'commission' => fake()->randomFloat(2, 5, 50),
            'disabled' => fake()->boolean(),
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
     * Define the model's state for 'commission_more_100'.
     *
     * @return Factory
     */
    public function stateCommissionMore100(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'commission' => 101,
            ];
        });
    }

    /**
     * Define the model's state for 'commission_less_0'.
     *
     * @return Factory
     */
    public function stateCommissionLess0(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'commission' => -1,
            ];
        });
    }
}
