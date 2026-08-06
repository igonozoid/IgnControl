<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' Ltda',
            'tax_id' => $this->faker->numerify('##.###.###/####-##'),
            'base_currency_code' => 'BRL',
            'is_active' => true,
            // Testes existentes assumem que RH/Estoque/Vendas/Rural já
            // estão disponíveis assim que a permissão do usuário libera
            // — então a fábrica nasce com tudo ligado por padrão. Quem
            // quiser testar uma empresa sem algum módulo usa
            // ->state(['enabled_modules' => [...]]) explicitamente.
            'enabled_modules' => \App\Models\Company::OPTIONAL_MODULES,
        ];
    }
}
