<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralActivity> */
class RuralActivityFactory extends Factory
{
    protected $model = RuralActivity::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'activity_type' => 'tech_visit',
            'status' => 'planned',
            'scheduled_date' => now()->toDateString(),
        ];
    }
}
