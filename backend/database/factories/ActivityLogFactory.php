<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'event' => 'product.updated',
            'subject_type' => 'App\\Models\\Product',
            'subject_id' => 1,
        ];
    }
}
