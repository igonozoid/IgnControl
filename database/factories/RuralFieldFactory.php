<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RuralField;
use App\Models\RuralProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuralField> */
class RuralFieldFactory extends Factory
{
    protected $model = RuralField::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'property_id' => RuralProperty::factory(),
            'name' => 'Talhão '.$this->faker->unique()->numberBetween(1, 999),
            'display_label' => 'Talhão',
            'field_type' => 'crop',
            'size_area' => 10,
            'size_unit' => 'ha',
            'is_active' => true,
        ];
    }
}
