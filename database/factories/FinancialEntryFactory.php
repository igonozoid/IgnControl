<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialEntry> */
class FinancialEntryFactory extends Factory
{
    protected $model = FinancialEntry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'financial_account_id' => FinancialAccount::factory(),
            'currency_code' => 'BRL',
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'description' => $this->faker->sentence(4),
            'due_date' => now()->toDateString(),
            'movement_date' => now()->toDateString(),
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);
    }
}
