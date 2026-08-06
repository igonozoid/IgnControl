<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMovement> */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 1,
            'unit_cost' => 0,
            'total_cost' => 0,
        ];
    }
}
