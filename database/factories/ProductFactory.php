<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'product_type' => 'product',
            'unit_code' => 'UN',
            'default_sale_price' => 0,
            'default_cost' => 0,
            'controls_stock' => true,
            'is_active' => true,
        ];
    }
}
