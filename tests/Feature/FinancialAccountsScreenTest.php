<?php

namespace Tests\Feature;

use App\Livewire\FinancialAccounts\Index;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialAccountsScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Toda conta financeira exige uma moeda válida (FK) — garante
        // que BRL existe antes de cada teste, em vez de repetir isso
        // em cada método.
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

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

    public function test_list_only_shows_accounts_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        FinancialAccount::factory()->create(['company_id' => $companyA->id, 'name' => 'Conta A']);
        FinancialAccount::factory()->create(['company_id' => $companyB->id, 'name' => 'Conta B']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Conta A')
            ->assertDontSee('Conta B');
    }

    public function test_read_only_user_cannot_see_create_button_action(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = $this->userWithLevel($company, 'read');

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_full_access_user_can_create_an_account(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = $this->userWithLevel($company, 'full');

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Conta Nova')
            ->set('type', 'bank')
            ->set('currency_code', 'BRL')
            ->set('opening_balance', '100')
            ->call('save');

        $this->assertDatabaseHas('financial_accounts', [
            'company_id' => $company->id,
            'name' => 'Conta Nova',
        ]);
    }

    public function test_full_access_user_can_delete_an_account(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $account->id);

        $this->assertDatabaseMissing('financial_accounts', ['id' => $account->id]);
    }
}
