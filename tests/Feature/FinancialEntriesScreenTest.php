<?php

namespace Tests\Feature;

use App\Livewire\FinancialEntries\Index;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialEntriesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'financial',
            'level' => $level,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    public function test_list_only_shows_entries_of_the_active_tab_type(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Aluguel',
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'description' => 'Venda de produto',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->assertSee('Aluguel')
            ->assertDontSee('Venda de produto');
    }

    public function test_list_only_shows_entries_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        $accountA = FinancialAccount::factory()->create(['company_id' => $companyA->id]);
        $accountB = FinancialAccount::factory()->create(['company_id' => $companyB->id]);

        FinancialEntry::factory()->create([
            'company_id' => $companyA->id,
            'financial_account_id' => $accountA->id,
            'type' => 'expense',
            'description' => 'Empresa A',
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $companyB->id,
            'financial_account_id' => $accountB->id,
            'type' => 'expense',
            'description' => 'Empresa B',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->assertSee('Empresa A')
            ->assertDontSee('Empresa B');
    }

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_an_expense(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->set('financial_account_id', $account->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '150.00')
            ->set('due_date', now()->toDateString())
            ->set('description', 'Conta de luz')
            ->call('save');

        $this->assertDatabaseHas('financial_entries', [
            'company_id' => $company->id,
            'type' => 'expense',
            'description' => 'Conta de luz',
            'status' => 'pending',
        ]);

        // "Pago em" veio em branco do formulário (string vazia, não null).
        // No SQLite dos testes isso passaria despercebido, mas o MySQL
        // real rejeita '' numa coluna DATE — por isso este assert verifica
        // explicitamente que fica gravado como NULL, não ''.
        $entry = FinancialEntry::query()->where('description', 'Conta de luz')->firstOrFail();
        $this->assertNull($entry->paid_date);
    }

    public function test_full_access_user_can_create_a_transfer_between_two_accounts(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $origin = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $destination = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'transfer')
            ->call('create')
            ->set('financial_account_id', $origin->id)
            ->set('destination_account_id', $destination->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '500.00')
            ->set('due_date', now()->toDateString())
            ->call('save');

        $this->assertDatabaseHas('financial_entries', [
            'company_id' => $company->id,
            'type' => 'transfer',
            'financial_account_id' => $origin->id,
            'destination_account_id' => $destination->id,
        ]);

        // Mesma moeda nas duas pontas — não deveria ter pedido valor de
        // destino nem taxa (ficam nulos).
        $entry = FinancialEntry::query()->where('financial_account_id', $origin->id)->firstOrFail();
        $this->assertNull($entry->destination_amount);
        $this->assertNull($entry->exchange_rate);
    }

    public function test_full_access_user_can_create_a_cross_currency_transfer_with_fee(): void
    {
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'symbol' => 'US$']);

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $origin = FinancialAccount::factory()->create(['company_id' => $company->id, 'currency_code' => 'USD']);
        $destination = FinancialAccount::factory()->create(['company_id' => $company->id, 'currency_code' => 'BRL']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'transfer')
            ->call('create')
            ->set('financial_account_id', $origin->id)
            ->set('destination_account_id', $destination->id)
            ->assertSet('isCrossCurrencyTransfer', true)
            ->set('currency_code', 'USD')
            ->set('amount', '100.00')
            ->set('destination_amount', '540.00')
            ->set('fee_amount', '5.00')
            ->set('due_date', now()->toDateString())
            ->call('save');

        $this->assertDatabaseHas('financial_entries', [
            'company_id' => $company->id,
            'type' => 'transfer',
            'financial_account_id' => $origin->id,
            'destination_account_id' => $destination->id,
            'amount' => '100.0000',
            'destination_amount' => '540.0000',
            'fee_amount' => '5.0000',
            'exchange_rate' => '5.400000',
        ]);
    }

    public function test_destination_amount_is_required_for_cross_currency_transfers(): void
    {
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'symbol' => 'US$']);

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $origin = FinancialAccount::factory()->create(['company_id' => $company->id, 'currency_code' => 'USD']);
        $destination = FinancialAccount::factory()->create(['company_id' => $company->id, 'currency_code' => 'BRL']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'transfer')
            ->call('create')
            ->set('financial_account_id', $origin->id)
            ->set('destination_account_id', $destination->id)
            ->set('amount', '100.00')
            ->set('due_date', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['destination_amount']);
    }

    public function test_marking_as_paid_updates_status_and_paid_date(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'status' => 'pending',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')->call('markAsPaid', $entry->id);

        $entry->refresh();
        $this->assertSame('paid', $entry->status);
        $this->assertNotNull($entry->paid_date);
    }

    public function test_full_access_user_can_delete_an_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')->call('delete', $entry->id);

        $this->assertDatabaseMissing('financial_entries', ['id' => $entry->id]);
    }

    public function test_document_number_and_movement_date_are_saved(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->set('financial_account_id', $account->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '80.00')
            ->set('due_date', '2026-02-15')
            ->set('document_number', 'NF 4521')
            ->set('movementEqualsDue', false)
            ->set('movement_date', '2026-01-31')
            ->set('description', 'Serviço de janeiro pago em fevereiro')
            ->call('save');

        $this->assertDatabaseHas('financial_entries', [
            'company_id' => $company->id,
            'document_number' => 'NF 4521',
            'movement_date' => '2026-01-31',
            'due_date' => '2026-02-15',
        ]);
    }

    public function test_movement_date_follows_due_date_while_checkbox_is_checked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->assertSet('movementEqualsDue', true)
            ->set('due_date', '2026-03-10')
            ->assertSet('movement_date', '2026-03-10');
    }

    public function test_installments_generate_multiple_entries_with_shared_movement_date(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->set('financial_account_id', $account->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '100.00')
            ->set('due_date', '2026-01-10')
            ->set('description', 'Compra parcelada')
            ->set('installmentsEnabled', true)
            ->set('installmentsCount', '3')
            ->call('save');

        $entries = FinancialEntry::query()
            ->where('company_id', $company->id)
            ->orderBy('due_date')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame('2026-01-10', $entries[0]->due_date->toDateString());
        $this->assertSame('2026-02-10', $entries[1]->due_date->toDateString());
        $this->assertSame('2026-03-10', $entries[2]->due_date->toDateString());

        // Todas com a mesma competência (é a mesma compra).
        $this->assertTrue($entries->every(fn ($e) => $e->movement_date->toDateString() === '2026-01-10'));

        // 100,00 / 3 = 33,33 + 33,33 + 33,34 (a última absorve o resto).
        // amount é decimal:4 no model, por isso compara com 4 casas.
        $this->assertSame('33.3300', (string) $entries[0]->amount);
        $this->assertSame('33.3300', (string) $entries[1]->amount);
        $this->assertSame('33.3400', (string) $entries[2]->amount);

        $this->assertStringContainsString('(1/3)', $entries[0]->description);
        $this->assertStringContainsString('(3/3)', $entries[2]->description);
    }

    public function test_defaults_to_the_current_month_and_year_on_load(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSet('period', (string) (now()->month - 1))
            ->assertSet('year', (string) now()->year);
    }

    public function test_period_quarter_filters_entries_within_that_quarter(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Despesa de março',
            'due_date' => '2026-03-05',
        ]);
        $this->actingAs($user);

        // 1º trimestre (período 12) = jan/fev/mar.
        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('period', '12')->set('year', '2026')
            ->assertSee('Despesa de março');

        // 2º trimestre (período 13) = abr/mai/jun — não deveria achar.
        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('period', '13')->set('year', '2026')
            ->assertDontSee('Despesa de março');
    }

    public function test_period_todo_periodo_ignores_year(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Despesa antiga',
            'due_date' => '2019-06-01',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('period', '19')->set('year', '2026')
            ->assertSee('Despesa antiga');
    }

    public function test_filter_by_movement_date_finds_entry_due_in_a_different_month(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Serviço de janeiro',
            'movement_date' => '2026-01-20',
            'due_date' => '2026-02-15',
        ]);
        $this->actingAs($user);

        // Filtrando por vencimento (padrão), janeiro (período 0) não
        // deveria achar nada.
        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('period', '0')->set('year', '2026')
            ->assertDontSee('Serviço de janeiro');

        // Filtrando por competência, aparece em janeiro.
        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('filterDateType', 'movement')
            ->set('period', '0')->set('year', '2026')
            ->assertSee('Serviço de janeiro');
    }

    public function test_filter_by_both_dates_finds_entry_matching_either_one(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Serviço de janeiro',
            'movement_date' => '2026-01-20',
            'due_date' => '2026-02-15',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('filterDateType', 'both')
            ->set('period', '1')->set('year', '2026') // fevereiro
            ->assertSee('Serviço de janeiro');

        Livewire::test(Index::class)->set('tab', 'expense')
            ->set('filterDateType', 'both')
            ->set('period', '0')->set('year', '2026') // janeiro
            ->assertSee('Serviço de janeiro');
    }

    public function test_installments_are_not_offered_when_editing_an_existing_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('edit', $entry->id)
            ->assertDontSee('Parcelar (gera vários lançamentos mensais)');
    }
}
