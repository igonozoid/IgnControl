<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\RuralActivity;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\RuralActivityService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuralActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompany(): Company
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        return $company;
    }

    public function test_planned_activity_does_not_touch_stock(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $activity = app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'spraying',
            'status' => 'planned',
        ], [
            ['product_id' => $product->id, 'quantity' => 10],
        ]);

        $this->assertSame('planned', $activity->status);
        $this->assertSame(0, StockMovement::query()->where('reference_type', 'RURAL_ACTIVITY')->count());
        $this->assertSame(100.0, app(StockService::class)->available($product->id));
    }

    public function test_done_activity_consumes_stock(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $activity = app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 15],
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'consumption_out',
            'reference_type' => 'RURAL_ACTIVITY',
            'reference_id' => $activity->id,
        ]);
        $this->assertSame(85.0, app(StockService::class)->available($product->id));
    }

    public function test_editing_a_done_activity_recreates_items_and_movements_without_duplicating(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $activity = app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 10],
        ]);

        app(RuralActivityService::class)->upsert($activity, [
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 20],
        ]);

        $this->assertSame(1, StockMovement::query()->where('reference_type', 'RURAL_ACTIVITY')->where('reference_id', $activity->id)->count());
        $this->assertSame(1, $activity->fresh()->items()->count());
        $this->assertSame(80.0, app(StockService::class)->available($product->id));
    }

    public function test_consuming_beyond_available_stock_throws(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->expectException(InsufficientStockException::class);

        app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);
    }

    public function test_cancel_reverses_consumption(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $activity = app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'spraying',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 10],
        ]);

        app(RuralActivityService::class)->cancel($activity);

        $activity->refresh();
        $this->assertSame('cancelled', $activity->status);
        $this->assertSame(100.0, app(StockService::class)->available($product->id));
    }

    public function test_activity_with_non_stock_controlled_product_does_not_post_movement(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id, 'controls_stock' => false]);

        $activity = app(RuralActivityService::class)->upsert(null, [
            'activity_type' => 'tech_visit',
            'status' => 'done',
            'performed_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $this->assertSame(0, StockMovement::query()->where('reference_type', 'RURAL_ACTIVITY')->count());
        $this->assertSame(1, RuralActivity::query()->findOrFail($activity->id)->items()->count());
    }
}
