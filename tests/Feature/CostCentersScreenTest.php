<?php

namespace Tests\Feature;

use App\Livewire\CostCenters\Index;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CostCentersScreenTest extends TestCase
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

    public function test_list_only_shows_cost_centers_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        CostCenter::factory()->create(['company_id' => $companyA->id, 'name' => 'Comercial']);
        CostCenter::factory()->create(['company_id' => $companyB->id, 'name' => 'Outra Empresa']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Comercial')
            ->assertDontSee('Outra Empresa');
    }

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_and_delete(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Administrativo')
            ->set('code', 'ADM')
            ->call('save');

        $this->assertDatabaseHas('cost_centers', [
            'company_id' => $company->id,
            'name' => 'Administrativo',
            'code' => 'ADM',
        ]);

        $costCenter = CostCenter::query()->where('name', 'Administrativo')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $costCenter->id);

        $this->assertDatabaseMissing('cost_centers', ['id' => $costCenter->id]);
    }

    public function test_full_access_user_can_set_applies_to_and_budget_fields(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Marketing')
            ->set('applies_to_expense', true)
            ->set('applies_to_revenue', false)
            ->set('expense_budget', '15000.50')
            ->call('save');

        $this->assertDatabaseHas('cost_centers', [
            'company_id' => $company->id,
            'name' => 'Marketing',
            'applies_to_expense' => true,
            'applies_to_revenue' => false,
            'expense_budget' => '15000.50',
            'revenue_projection' => null,
        ]);
    }

    public function test_cost_center_that_only_applies_to_revenue_is_not_offered_for_expense_entries(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        CostCenter::factory()->create([
            'company_id' => $company->id,
            'name' => 'Comercial (só receita)',
            'applies_to_expense' => false,
            'applies_to_revenue' => true,
        ]);
        $this->actingAs($user);

        Livewire::test(\App\Livewire\FinancialEntries\Index::class)
            ->set('tab', 'expense')
            ->assertDontSee('Comercial (só receita)');

        Livewire::test(\App\Livewire\FinancialEntries\Index::class)
            ->set('tab', 'income')
            ->assertSee('Comercial (só receita)');
    }

    public function test_filter_by_status_only_shows_matching_cost_centers(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');

        CostCenter::factory()->create(['company_id' => $company->id, 'name' => 'Ativo Um', 'is_active' => true]);
        CostCenter::factory()->create(['company_id' => $company->id, 'name' => 'Inativo Um', 'is_active' => false]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterStatus', 'inactive')
            ->assertSee('Inativo Um')
            ->assertDontSee('Ativo Um');
    }
}
