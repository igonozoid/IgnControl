<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesScreenTest extends TestCase
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

    public function test_list_only_shows_categories_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        Category::factory()->create(['company_id' => $companyA->id, 'name' => 'Vendas']);
        Category::factory()->create(['company_id' => $companyB->id, 'name' => 'Outra Empresa']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Vendas')
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
            ->set('name', 'Despesas com marketing')
            ->set('type', 'expense')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'company_id' => $company->id,
            'name' => 'Despesas com marketing',
        ]);

        $category = Category::query()->where('name', 'Despesas com marketing')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_can_deactivate_a_category_and_filter_by_status(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $category = Category::factory()->create(['company_id' => $company->id, 'name' => 'Antiga', 'is_active' => true]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $category->id)
            ->set('is_active', false)
            ->call('save');

        $this->assertFalse($category->refresh()->is_active);

        // "Antiga" também aparece sempre no <select> de categoria-pai do
        // formulário (que lista todas, sem filtro) — por isso o assert
        // precisa mirar na LINHA da tabela (wire:key), não no texto solto,
        // mesmo padrão já usado no teste de "only needs review".
        Livewire::test(Index::class)->set('filterStatus', 'inactive')
            ->assertSee('wire:key="category-'.$category->id.'"', false);
        Livewire::test(Index::class)->set('filterStatus', 'active')
            ->assertDontSee('wire:key="category-'.$category->id.'"', false);
    }

    public function test_inactive_category_is_not_offered_as_an_option_for_new_entries(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        \App\Models\Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        \App\Models\FinancialAccount::factory()->create(['company_id' => $company->id]);
        Category::factory()->create(['company_id' => $company->id, 'name' => 'Categoria Inativa', 'type' => 'expense', 'is_active' => false]);
        $this->actingAs($user);

        Livewire::test(\App\Livewire\FinancialEntries\Index::class)->set('tab', 'expense')
            ->call('create')
            ->assertDontSee('Categoria Inativa');
    }
}
