<?php

namespace Tests\Feature;

use App\Livewire\RuralActivities\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RuralActivity;
use App\Models\RuralOccurrence;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuralActivitiesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'rural',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_rural_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_full_access_user_can_create_a_done_activity_and_consume_stock(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 50,
        ]);

        Livewire::test(Index::class)
            ->call('create')
            ->set('activity_type', 'spraying')
            ->set('status', 'done')
            ->set('performed_date', now()->toDateString())
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '10')
            ->call('save')
            ->assertSet('showForm', false);

        $activity = RuralActivity::query()->firstOrFail();
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'consumption_out',
            'reference_id' => $activity->id,
        ]);
        $this->assertSame(40.0, app(StockService::class)->available($product->id));
    }

    public function test_cancelling_an_activity_reverses_consumption(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 50,
        ]);

        Livewire::test(Index::class)
            ->call('create')
            ->set('activity_type', 'spraying')
            ->set('status', 'done')
            ->set('performed_date', now()->toDateString())
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '10')
            ->call('save');

        $activity = RuralActivity::query()->firstOrFail();

        Livewire::test(Index::class)->call('cancelActivity', $activity->id);

        $activity->refresh();
        $this->assertSame('cancelled', $activity->status);
        $this->assertSame(50.0, app(StockService::class)->available($product->id));
    }

    public function test_full_access_user_can_create_and_delete_an_occurrence(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('createOccurrence')
            ->set('occurrence_date', now()->toDateString())
            ->set('occurrence_type', 'pest')
            ->set('severity', 'high')
            ->set('description', 'Lagarta detectada no talhão 3')
            ->call('saveOccurrence')
            ->assertSet('showOccurrenceForm', false);

        $occurrence = RuralOccurrence::query()->firstOrFail();
        $this->assertSame('Lagarta detectada no talhão 3', $occurrence->description);

        Livewire::test(Index::class)->call('deleteOccurrence', $occurrence->id);

        $this->assertDatabaseMissing('rural_occurrences', ['id' => $occurrence->id]);
    }
}
