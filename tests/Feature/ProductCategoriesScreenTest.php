<?php

namespace Tests\Feature;

use App\Livewire\ProductCategories\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategoriesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'inventory',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_inventory_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
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
            ->set('name', 'Insumos')
            ->call('save');

        $this->assertDatabaseHas('product_categories', [
            'company_id' => $company->id,
            'name' => 'Insumos',
        ]);

        $category = ProductCategory::query()->where('name', 'Insumos')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $category->id);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_filter_by_status_only_shows_matching_categories(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $active = ProductCategory::factory()->create(['company_id' => $company->id, 'name' => 'Categoria Ativa', 'is_active' => true]);
        $inactive = ProductCategory::factory()->create(['company_id' => $company->id, 'name' => 'Categoria Inativa', 'is_active' => false]);
        $this->actingAs($user);

        // Assertion escopada por wire:key: a palavra "Ativa" sozinha
        // também aparece sempre no filtro de situação ("Ativas"), então
        // assertDontSee('Ativa') bruto daria falso positivo.
        Livewire::test(Index::class)
            ->set('filterStatus', 'inactive')
            ->assertSee('product-category-'.$inactive->id, false)
            ->assertDontSee('product-category-'.$active->id, false);
    }
}
