<?php
/*
 * File name: ExperienceFactory.php
 * Last modified: 2024.04.11 at 12:39:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Experience;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperienceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Experience::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->text(127),
            'description' => fake()->realText(),
            'salon_id' => Salon::all()->random()->id
        ];
    }

    public function titleMore127Char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => fake()->paragraph(20),
            ];
        });
    }

    public function notExistSalonId(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'salon_id' => 500000, // not exist id
            ];
        });
    }
}
