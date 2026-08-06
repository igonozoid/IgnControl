<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrderItem> */
class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'description_snapshot' => $this->faker->words(2, true),
            'quantity' => 1,
            'unit_code' => 'UN',
            'unit_price' => 10,
            'line_total' => 10,
        ];
    }
}
