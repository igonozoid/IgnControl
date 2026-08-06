<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralOccurrence> */
class RuralOccurrenceFactory extends Factory
{
    protected $model = RuralOccurrence::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'occurrence_date' => now()->toDateString(),
            'occurrence_type' => 'pest',
            'severity' => 'normal',
            'description' => $this->faker->sentence(),
            'status' => 'open',
        ];
    }
}
