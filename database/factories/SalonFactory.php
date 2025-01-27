<?php
/*
 * File name: SalonFactory.php
 * Last modified: 2024.04.11 at 12:59:41
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\Address;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SalonFactory
 * @package Database\Factories
 */
class SalonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Salon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Vibes Hair', 'Spa Skin Care', 'Waxing', 'Miami Nail', 'Terra Bella Day', 'Healing Hands Massage', 'Damisa Thai Massage', 'Blanc Beauty Smile', 'The Comfort Zone Spa', 'Esthetics center', 'Studio Lux', 'Royalty Nails', 'Beauty Attraction']) . " " . fake()->company,
            'description' => fake()->text,
            'salon_level_id' => fake()->numberBetween(2, 4),
            'address_id' => Address::all()->random()->id,
            'phone_number' => fake()->phoneNumber,
            'mobile_number' => fake()->phoneNumber,
            'availability_range' => fake()->randomFloat(2, 6000, 15000),
            'available' => fake()->boolean(100),
            'featured' => fake()->boolean(40),
            'accepted' => fake()->boolean(95),
        ];
    }
}
