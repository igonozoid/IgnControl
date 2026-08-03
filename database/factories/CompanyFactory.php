<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' Ltda',
            'tax_id' => $this->faker->numerify('##.###.###/####-##'),
            'base_currency_code' => 'BRL',
            'is_active' => true,
        ];
    }
}
