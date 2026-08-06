<?php

namespace Tests\Feature;

use App\Livewire\Reports\RuralCosts;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RuralField;
use App\Models\RuralProperty;
use App\Models\User;
use App\Services\RuralActivityService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuralCostsReportTest extends TestCase
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

        Livewire::test(RuralCosts::class)->assertForbidden();
    }

    public function test_report_aggregates_consumption_cost_by_field(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        $product = Product::factory()->create(['company_id' => $company->id, 'default_cost' => 10]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        app(RuralActivityService::class)->upsert(null, [
            'field_id' => $field->id,
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => 'RURAL_ACTIVITY',
            'movement_type' => 'consumption_out',
            'total_cost' => 50,
        ]);

        $component = Livewire::test(RuralCosts::class);

        $this->assertEqualsWithDelta(50.0, $component->viewData('totalCost'), 0.001);
        $this->assertEqualsWithDelta(50.0, $component->viewData('byField')[$field->name], 0.001);
    }
}
