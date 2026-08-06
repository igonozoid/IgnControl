<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralActivity;
use App\Models\RuralActivityItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralActivityItem> */
class RuralActivityItemFactory extends Factory
{
    protected $model = RuralActivityItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'activity_id' => RuralActivity::factory(),
            'quantity' => 1,
            'unit_code' => 'UN',
        ];
    }
}
