<?php
/*
 * File name: OptionFactory.php
 * Last modified: 2024.04.18 at 17:53:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\EService;
use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class OptionFactory
 * @package Database\Factories
 */
class OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Addon 1', 'Addon 2', 'Addon 3', 'Addon 4']),
            'description' => fake()->sentence(4),
            'price' => fake()->randomFloat(2, 10, 50),
            'e_service_id' => EService::all()->random()->id,
            'option_group_id' => OptionGroup::all()->random()->id,
        ];
    }
}
