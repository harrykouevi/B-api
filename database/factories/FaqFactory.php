<?php
/*
 * File name: FaqFactory.php
 * Last modified: 2024.04.11 at 12:39:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Faq::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'question' => fake()->text(100),
            'answer' => fake()->realText(),
            'faq_category_id' => fake()->numberBetween(1, 4)
        ];
    }
}
