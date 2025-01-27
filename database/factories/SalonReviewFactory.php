<?php
/*
 * File name: SalonReviewFactory.php
 * Last modified: 2024.04.11 at 12:59:41
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\Booking;
use App\Models\SalonReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SalonReviewFactory
 * @package Database\Factories
 */
class SalonReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalonReview::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            "review" => fake()->realText(100),
            "rate" => fake()->numberBetween(1, 5),
            "booking_id" => Booking::all()->random()->id,
        ];
    }
}
