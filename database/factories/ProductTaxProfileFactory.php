<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProductTaxProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductTaxProfile> */
class ProductTaxProfileFactory extends Factory
{
    protected $model = ProductTaxProfile::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'tax_mode' => 'rate',
            'default_rate_percent' => 0,
            'is_active' => true,
        ];
    }
}
