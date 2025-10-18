<?php

namespace Database\Factories;

use App\Models\OptionTemplate;
use App\Models\ServiceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class OptionTemplateFactory extends Factory
{
    protected $model = OptionTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'price' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'service_template_id' => ServiceTemplate::factory(),
        ];
    }
}
