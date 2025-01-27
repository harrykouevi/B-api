<?php /*
 * File name: FaqCategoryFactory.php
 * Last modified: 2024.04.11 at 12:39:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

/** @noinspection PhpUnusedLocalVariableInspection */

namespace Database\Factories;

use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FaqCategory::class;
    private array $names = ['Service', 'Payment', 'Support', 'Salons', 'Misc'];
    private int $i = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->names[$this->i++],
        ];
    }
}
