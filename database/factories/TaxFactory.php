<?php
/*
 * File name: TaxFactory.php
 * Last modified: 2024.04.11 at 14:23:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->randomFloat(2, 0, 100);
        return [
            'name' => fake()->randomElement(['Maintenance','Tools Fee', 'Tax']).' '. $value . '%' ,
            'value' => $value,
            'type' => fake()->randomElement(['percent', 'fixed']),
        ];
    }
}
