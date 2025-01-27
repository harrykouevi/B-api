<?php
/*
 * File name: SalonUserFactory.php
 * Last modified: 2024.04.11 at 12:59:41
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\Salon;
use App\Models\SalonUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SalonUserFactory
 * @package Database\Factories
 */
class SalonUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalonUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->randomElement([2, 4, 6]),
            'salon_id' => Salon::all()->random()->id
        ];
    }
}
