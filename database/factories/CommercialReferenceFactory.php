<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CommercialReference;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommercialReference> */
class CommercialReferenceFactory extends Factory
{
    protected $model = CommercialReference::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contact_id' => Contact::factory(),
            'name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
