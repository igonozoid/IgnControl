<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Moedas básicas
        $brl = Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real brasileiro', 'symbol' => 'R$']);
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar americano', 'symbol' => 'US$']);
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€']);

        ExchangeRate::firstOrCreate(
            ['currency_code' => 'USD', 'rate_date' => now()->toDateString()],
            ['rate_to_base' => 5.4000]
        );
        ExchangeRate::firstOrCreate(
            ['currency_code' => 'EUR', 'rate_date' => now()->toDateString()],
            ['rate_to_base' => 5.9000]
        );

        // Empresa de demonstração
        $company = Company::firstOrCreate(
            ['name' => 'Empresa Demo'],
            ['legal_name' => 'Empresa Demo Ltda', 'base_currency_code' => 'BRL', 'is_active' => true]
        );

        // Usuário admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@ignfinance.local'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('password'),
                'current_company_id' => $company->id,
            ]
        );

        $company->users()->syncWithoutDetaching([$admin->id => ['role' => 'owner']]);

        foreach (Permission::MODULES as $module) {
            Permission::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $admin->id, 'module' => $module],
                ['level' => 'full']
            );
        }

        // Estrutura básica para começar a usar
        FinancialAccount::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Caixa'],
            ['type' => 'cash', 'currency_code' => 'BRL', 'opening_balance' => 0]
        );
        FinancialAccount::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Banco Principal'],
            ['type' => 'bank', 'currency_code' => 'BRL', 'opening_balance' => 0]
        );

        CostCenter::firstOrCreate(['company_id' => $company->id, 'name' => 'Administrativo'], ['code' => 'ADM']);

        Category::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Vendas', 'type' => 'income']
        );
        Category::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Despesas gerais', 'type' => 'expense']
        );

        $this->command->info('Seed concluído. Login: admin@ignfinance.local / senha: password');
    }
}
