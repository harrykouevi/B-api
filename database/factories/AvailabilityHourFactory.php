<?php
/*
 * File name: AvailabilityHourFactory.php
 * Last modified: 2024.04.11 at 11:40:23
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\AvailabilityHour;
use App\Models\Salon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AvailabilityHourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AvailabilityHour::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day' => Str::lower(fake()->randomElement(Carbon::getDays())),
            'start_at' => str_pad(fake()->numberBetween(2, 12), 2, '0', STR_PAD_LEFT) . ":00",
            'end_at' => fake()->numberBetween(13, 23) . ":00",
            'data' => fake()->text(50),
            'salon_id' => Salon::all()->random()->id
        ];
    }

    public function day_more_16_char(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'day' => fake()->paragraph(3),
            ];
        });
    }

    public function end_at_lest_start_at(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'start_at' => fake()->numberBetween(16, 21) . ":20",
                'end_at' => fake()->numberBetween(10, 13) . ":30",
            ];
        });
    }

    public function not_exist_salon_id(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'salon_id' => 500000, // not exist id
            ];
        });
    }
}
