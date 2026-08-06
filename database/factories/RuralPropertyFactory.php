<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralProperty> */
class RuralPropertyFactory extends Factory
{
    protected $model = RuralProperty::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Fazenda '.$this->faker->unique()->word(),
            'city' => $this->faker->city(),
            'state' => 'SP',
            'country' => 'Brasil',
            'is_active' => true,
        ];
    }
}
