<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrder> */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sale_type' => 'sale',
            'status' => 'draft',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
        ];
    }
}
