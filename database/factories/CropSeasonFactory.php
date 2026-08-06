<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CropSeason;
use App\Models\RuralField;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CropSeason> */
class CropSeasonFactory extends Factory
{
    protected $model = CropSeason::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'field_id' => RuralField::factory(),
            'crop_name' => 'Soja',
            'season_label' => 'Safra '.$this->faker->unique()->numberBetween(2020, 2099),
            'area_unit' => 'ha',
            'status' => 'planned',
            'yield_unit' => 'kg',
        ];
    }
}
