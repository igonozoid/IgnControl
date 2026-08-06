<?php

namespace Tests\Feature;

use App\Livewire\StockMovements\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockMovementsScreenTest extends TestCase
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

    public function test_full_access_user_can_post_a_manual_movement(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openMovementForm')
            ->set('product_id', (string) $product->id)
            ->set('movement_type', 'manual_in')
            ->set('movement_date', now()->toDateString())
            ->set('quantity', '20')
            ->call('saveMovement')
            ->assertSet('showMovementForm', false);

        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
        ]);
    }

    public function test_outbound_movement_beyond_available_shows_form_error(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openMovementForm')
            ->set('product_id', (string) $product->id)
            ->set('movement_type', 'adjustment_out')
            ->set('movement_date', now()->toDateString())
            ->set('quantity', '5')
            ->call('saveMovement')
            ->assertSet('showMovementForm', true)
            ->assertSee('Estoque insuficiente');

        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    }

    public function test_full_access_user_can_transfer_between_locations(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $origin = StockLocation::factory()->create(['company_id' => $company->id]);
        $destination = StockLocation::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openMovementForm')
            ->set('product_id', (string) $product->id)
            ->set('location_id', (string) $origin->id)
            ->set('movement_type', 'manual_in')
            ->set('movement_date', now()->toDateString())
            ->set('quantity', '10')
            ->call('saveMovement');

        Livewire::test(Index::class)
            ->call('openTransferForm')
            ->set('transfer_product_id', (string) $product->id)
            ->set('transfer_from_location_id', (string) $origin->id)
            ->set('transfer_to_location_id', (string) $destination->id)
            ->set('transfer_date', now()->toDateString())
            ->set('transfer_quantity', '4')
            ->call('saveTransfer')
            ->assertSet('showTransferForm', false);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_id' => $destination->id,
            'movement_type' => 'transfer_in',
        ]);
    }

    public function test_filter_by_product_only_shows_matching_movements(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $productA = Product::factory()->create(['company_id' => $company->id, 'name' => 'Produto A']);
        $productB = Product::factory()->create(['company_id' => $company->id, 'name' => 'Produto B']);
        $movementA = \App\Models\StockMovement::factory()->create(['company_id' => $company->id, 'product_id' => $productA->id]);
        $movementB = \App\Models\StockMovement::factory()->create(['company_id' => $company->id, 'product_id' => $productB->id]);
        $this->actingAs($user);

        // Escopado por wire:key da linha na tabela de movimentações — o
        // nome dos dois produtos também aparece sempre no quadro de
        // saldo e nos <select> dos formulários/filtros, então um
        // assertDontSee('Produto B') bruto daria falso positivo.
        Livewire::test(Index::class)
            ->set('filterProductId', (string) $productA->id)
            ->assertSee('movement-'.$movementA->id, false)
            ->assertDontSee('movement-'.$movementB->id, false);
    }
}
