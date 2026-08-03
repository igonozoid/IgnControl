<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEntryDecimalTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_keeps_exact_decimal_precision(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $account = FinancialAccount::factory()->create(['currency_code' => 'BRL']);

        $entry = FinancialEntry::factory()->create([
            'financial_account_id' => $account->id,
            'currency_code' => 'BRL',
            'amount' => '10.10',
        ]);

        // Valor exato — nada de erro de arredondamento binário de float.
        $this->assertSame('10.1000', $entry->fresh()->amount);
    }

    public function test_amount_column_is_stored_as_decimal_not_float_in_schema(): void
    {
        // Os testes rodam contra SQLite em memória (ver phpunit.xml), o
        // MySQL do XAMPP é usado só em desenvolvimento. SQLite não tem
        // um tipo DECIMAL nativo — ele aplica "type affinity" e reporta
        // a coluna como 'numeric', mesmo a migration declarando
        // decimal(15,4). O que este teste realmente precisa garantir é
        // que a coluna NÃO é float/double (que causaria erro de
        // arredondamento binário) — 'decimal' (MySQL) e 'numeric'
        // (SQLite) são os dois nomes aceitáveis para isso.
        $column = collect(\Illuminate\Support\Facades\Schema::getColumns('financial_entries'))
            ->firstWhere('name', 'amount');

        $this->assertNotNull($column);
        $this->assertContains($column['type_name'], ['decimal', 'numeric']);
        $this->assertNotContains($column['type_name'], ['float', 'double', 'real']);
    }
}
