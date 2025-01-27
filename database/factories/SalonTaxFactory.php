<?php
/*
 * File name: SalonTaxFactory.php
 * Last modified: 2024.04.11 at 12:59:41
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */


namespace Database\Factories;

use App\Models\Salon;
use App\Models\SalonTax;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SalonTaxFactory
 * @package Database\Factories
 */
class SalonTaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalonTax::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'tax_id' => Tax::all()->random()->id,
            'salon_id' => Salon::all()->random()->id
        ];
    }
}
