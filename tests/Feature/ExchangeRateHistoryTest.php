<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_uses_the_exchange_rate_from_its_due_date_not_the_latest_rate(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'symbol' => 'US$']);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $oldRate = ExchangeRate::factory()->create([
            'currency_code' => 'USD',
            'rate_date' => '2025-01-10',
            'rate_to_base' => '5.0000',
        ]);

        $newRate = ExchangeRate::factory()->create([
            'currency_code' => 'USD',
            'rate_date' => '2026-06-01',
            'rate_to_base' => '5.9000',
        ]);

        $account = FinancialAccount::factory()->create(['currency_code' => 'USD']);

        $entry = FinancialEntry::factory()->create([
            'financial_account_id' => $account->id,
            'currency_code' => 'USD',
            'due_date' => '2025-01-15',
        ]);

        $this->assertSame($oldRate->id, $entry->exchange_rate_id);
        $this->assertNotSame($newRate->id, $entry->exchange_rate_id);
    }

    public function test_editing_the_due_date_recalculates_the_exchange_rate(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'symbol' => 'US$']);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $oldRate = ExchangeRate::factory()->create([
            'currency_code' => 'USD',
            'rate_date' => '2025-01-10',
            'rate_to_base' => '5.0000',
        ]);

        $newRate = ExchangeRate::factory()->create([
            'currency_code' => 'USD',
            'rate_date' => '2026-06-01',
            'rate_to_base' => '5.9000',
        ]);

        $account = FinancialAccount::factory()->create(['currency_code' => 'USD']);

        $entry = FinancialEntry::factory()->create([
            'financial_account_id' => $account->id,
            'currency_code' => 'USD',
            'due_date' => '2025-01-15',
        ]);

        $this->assertSame($oldRate->id, $entry->exchange_rate_id);

        // Editar a data de vencimento pra um período com outra taxa
        // vigente precisa recalcular exchange_rate_id automaticamente.
        $entry->update(['due_date' => '2026-06-10']);

        $this->assertSame($newRate->id, $entry->fresh()->exchange_rate_id);
    }

    public function test_editing_unrelated_fields_does_not_change_the_exchange_rate(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'symbol' => 'US$']);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $rate = ExchangeRate::factory()->create([
            'currency_code' => 'USD',
            'rate_date' => '2025-01-10',
            'rate_to_base' => '5.0000',
        ]);

        $account = FinancialAccount::factory()->create(['currency_code' => 'USD']);

        $entry = FinancialEntry::factory()->create([
            'financial_account_id' => $account->id,
            'currency_code' => 'USD',
            'due_date' => '2025-01-15',
        ]);

        $entry->update(['description' => 'Descrição alterada']);

        $this->assertSame($rate->id, $entry->fresh()->exchange_rate_id);
    }

    public function test_rate_on_returns_null_when_there_is_no_rate_before_the_date(): void
    {
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€']);

        ExchangeRate::factory()->create([
            'currency_code' => 'EUR',
            'rate_date' => '2026-01-01',
            'rate_to_base' => '5.9000',
        ]);

        $this->assertNull(ExchangeRate::rateOn('EUR', '2020-01-01'));
    }
}
