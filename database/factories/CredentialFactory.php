<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Credential> */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category' => 'login',
            'title' => $this->faker->company(),
            'url' => $this->faker->url(),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'notes' => null,
        ];
    }
}
