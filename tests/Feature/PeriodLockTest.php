<?php

namespace Tests\Feature;

use App\Livewire\Admin\PeriodLock;
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

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $module, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => $module,
            'level' => $level,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    public function test_user_without_admin_full_access_is_blocked_from_the_screen(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'admin', 'read');
        $this->actingAs($user);

        Livewire::test(PeriodLock::class)->assertForbidden();
    }

    public function test_admin_can_set_the_lock_date(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'admin', 'full');
        $this->actingAs($user);

        Livewire::test(PeriodLock::class)
            ->set('locked_through', '2026-06-30')
            ->call('save');

        $this->assertSame('2026-06-30', $company->refresh()->locked_through->toDateString());
    }

    public function test_cannot_create_an_entry_dated_inside_the_locked_period(): void
    {
        $company = Company::factory()->create(['locked_through' => '2026-06-30']);
        $user = $this->userWithLevel($company, 'financial', 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->set('financial_account_id', $account->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '100.00')
            ->set('due_date', '2026-06-15')
            ->call('save')
            ->assertSee('período fechado');

        $this->assertDatabaseMissing('financial_entries', ['due_date' => '2026-06-15']);
    }

    public function test_can_create_an_entry_dated_after_the_locked_period(): void
    {
        $company = Company::factory()->create(['locked_through' => '2026-06-30']);
        $user = $this->userWithLevel($company, 'financial', 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('create')
            ->set('financial_account_id', $account->id)
            ->set('currency_code', 'BRL')
            ->set('amount', '100.00')
            ->set('due_date', '2026-07-01')
            ->call('save');

        $this->assertDatabaseHas('financial_entries', ['due_date' => '2026-07-01']);
    }

    public function test_cannot_edit_an_entry_inside_the_locked_period(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'due_date' => '2026-06-15',
            'description' => 'Original',
        ]);
        $company->update(['locked_through' => '2026-06-30']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')
            ->call('edit', $entry->id)
            ->set('description', 'Editado')
            ->call('save');

        $this->assertSame('Original', $entry->refresh()->description);
    }

    public function test_cannot_delete_an_entry_inside_the_locked_period(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'due_date' => '2026-06-15',
        ]);
        $company->update(['locked_through' => '2026-06-30']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')->call('delete', $entry->id);

        $this->assertDatabaseHas('financial_entries', ['id' => $entry->id]);
    }

    public function test_cannot_mark_a_locked_entry_as_paid(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'due_date' => '2026-06-15',
            'status' => 'pending',
        ]);
        $company->update(['locked_through' => '2026-06-30']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('tab', 'expense')->call('markAsPaid', $entry->id);

        $this->assertSame('pending', $entry->refresh()->status);
    }
}
