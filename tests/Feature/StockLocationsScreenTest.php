<?php

namespace Tests\Feature;

use App\Livewire\StockLocations\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockLocationsScreenTest extends TestCase
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

    public function test_full_access_user_can_create_and_delete(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Depósito Central')
            ->set('location_type', 'warehouse')
            ->call('save');

        $this->assertDatabaseHas('stock_locations', [
            'company_id' => $company->id,
            'name' => 'Depósito Central',
        ]);

        $location = StockLocation::query()->where('name', 'Depósito Central')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $location->id);

        $this->assertDatabaseMissing('stock_locations', ['id' => $location->id]);
    }

    public function test_cannot_delete_a_location_with_stock_movements(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $location = StockLocation::factory()->create(['company_id' => $company->id]);
        StockMovement::factory()->create(['company_id' => $company->id, 'location_id' => $location->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('delete', $location->id)
            ->assertSee('já existe movimentação de estoque', false);

        $this->assertDatabaseHas('stock_locations', ['id' => $location->id]);
    }
}
