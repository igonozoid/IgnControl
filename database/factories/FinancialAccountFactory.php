<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialAccount> */
class FinancialAccountFactory extends Factory
{
    protected $model = FinancialAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->word().' - Conta',
            'type' => $this->faker->randomElement(['cash', 'bank']),
            'currency_code' => 'BRL',
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }
}
