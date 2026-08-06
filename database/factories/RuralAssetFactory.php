<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralAsset> */
class RuralAssetFactory extends Factory
{
    protected $model = RuralAsset::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'asset_type' => 'machinery',
            'name' => 'Trator '.$this->faker->unique()->numberBetween(1, 999),
            'unit_code' => 'UN',
            'is_active' => true,
        ];
    }
}
