<?php

namespace Tests\Feature;

use App\Livewire\Products\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsScreenTest extends TestCase
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

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_a_product(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Parafuso 3/4')
            ->set('sku', 'PRF-001')
            ->set('product_type', 'product')
            ->set('default_sale_price', '2.50')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'company_id' => $company->id,
            'name' => 'Parafuso 3/4',
            'sku' => 'PRF-001',
            'controls_stock' => true,
        ]);
    }

    public function test_service_type_can_opt_out_of_stock_control(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Consultoria')
            ->set('product_type', 'service')
            ->set('controls_stock', false)
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Consultoria',
            'product_type' => 'service',
            'controls_stock' => false,
        ]);
    }

    public function test_cannot_delete_a_product_with_stock_movements(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        StockMovement::factory()->create(['company_id' => $company->id, 'product_id' => $product->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('delete', $product->id)
            ->assertSee('já existe movimentação de estoque', false);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_filter_by_type_only_shows_matching_products(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Produto Físico', 'product_type' => 'product']);
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Serviço X', 'product_type' => 'service']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterType', 'service')
            ->assertSee('Serviço X')
            ->assertDontSee('Produto Físico');
    }
}
