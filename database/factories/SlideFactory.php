<?php
/*
 * File name: SlideFactory.php
 * Last modified: 2024.04.11 at 13:35:11
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\EService;
use App\Models\Salon;
use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SlideFactory
 * @package Database\Factories
 */
class SlideFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Slide::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $eService = fake()->boolean;
        return [
            'order' => fake()->numberBetween(0, 5),
            'text' => fake()->sentence(4),
            'button' => fake()->randomElement(['Discover It', 'Book Now', 'Get Discount']),
            'text_position' => fake()->randomElement(['start', 'end', 'center']),
            'text_color' => '#25d366',
            'button_color' => '#25d366',
            'background_color' => '#ccccdd',
            'indicator_color' => '#25d366',
            'image_fit' => 'cover',
            'e_service_id' => $eService ? EService::all()->random()->id : null,
            'salon_id' => !$eService ? Salon::all()->random()->id : null,
            'enabled' => fake()->boolean,
        ];
    }
}
