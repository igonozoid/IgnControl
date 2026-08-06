<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockLocation> */
class StockLocationFactory extends Factory
{
    protected $model = StockLocation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->word().' - Depósito',
            'location_type' => 'warehouse',
            'is_active' => true,
        ];
    }
}
