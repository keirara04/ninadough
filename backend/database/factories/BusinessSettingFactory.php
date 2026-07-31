<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => ['sample' => $this->faker->word()],
            'is_public' => false,
        ];
    }
}
