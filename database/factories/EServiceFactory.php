<?php
/*
 * File name: EServiceFactory.php
 * Last modified: 2024.04.11 at 12:17:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\EService;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            'Haircut & Beard Trim/Eadge',
            'Haircut with eyebrows',
            'Skill press and style',
            'Wand curls',
            'Health trim',
            'Ponytail',
            'Wig consultation',
            'Braid down',
            'Shampoo & deep conditioning Treatment',
            'Keratin hair treatment',
            'Quick weave',
            'Quick weave removal',
            'Massage Services',
            'Thai Massage Services',
            'Facials Services',
            'Child haircut',
            'Balayage',
            'Brazilian Blowout',
            'Global Keratin treatment',
            'Neck trim',
            'Color correction',
            'Hair Botox',
            'Beard trim',
            'Relax the neck and back',
            'Body rub with hot stone',
            'Foot reflexology',
        ];
        $price = fake()->randomFloat(2, 10, 50);
        $discountPrice = $price - fake()->randomFloat(2, 1, 10);
        return [
            'name' => fake()->randomElement($services),
            'price' => $price,
            'discount_price' => fake()->randomElement([$discountPrice, 0]),
            'duration' => fake()->numberBetween(1, 5) . ":00",
            'description' => fake()->text,
            'featured' => fake()->boolean,
            'enable_booking' => fake()->boolean,
            'available' => fake()->boolean,
            'salon_id' => Salon::all()->random()->id
        ];
    }
}
