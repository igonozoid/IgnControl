<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExchangeRate> */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'currency_code' => Currency::factory(),
            'rate_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'rate_to_base' => $this->faker->randomFloat(4, 0.1, 10),
        ];
    }
}
