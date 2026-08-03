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
}
