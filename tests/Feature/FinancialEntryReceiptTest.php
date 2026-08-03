<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEntryReceiptTest extends TestCase
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

    public function test_user_with_read_access_can_view_the_receipt(): void
    {
        $company = Company::factory()->create(['name' => 'Empresa Teste']);
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Aluguel de agosto',
            'amount' => '1200.00',
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt', $entry))
            ->assertOk()
            ->assertSee('Empresa Teste')
            ->assertSee('Aluguel de agosto')
            ->assertSee('1.200,00');
    }

    public function test_user_without_any_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt', $entry))
            ->assertForbidden();
    }

    public function test_entry_from_another_company_is_not_found(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        $accountB = FinancialAccount::factory()->create(['company_id' => $companyB->id]);
        $entryB = FinancialEntry::factory()->create([
            'company_id' => $companyB->id,
            'financial_account_id' => $accountB->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt', $entryB))
            ->assertNotFound();
    }
}
