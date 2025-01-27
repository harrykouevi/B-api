<?php
/*
 * File name: GalleryFactory.php
 * Last modified: 2024.04.11 at 12:51:14
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class GalleryFactory
 * @package Database\Factories
 */
class GalleryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gallery::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence,
            'salon_id' => Salon::all()->random()->id
        ];
    }
}
