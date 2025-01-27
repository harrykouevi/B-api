<?php
/*
 * File name: EServiceCategoryFactory.php
 * Last modified: 2024.04.11 at 12:17:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;
use App\Models\Category;
use App\Models\EService;
use App\Models\EServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EServiceCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EServiceCategory::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::all()->random()->id,
            'e_service_id' => EService::all()->random()->id
        ];
    }
}
