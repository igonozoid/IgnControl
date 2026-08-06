<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CropSeason;
use App\Models\Product;
use App\Models\RuralField;
use App\Models\RuralProperty;
use App\Models\User;
use App\Services\CropSeasonService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropSeasonServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompany(): Company
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        return $company;
    }

    public function test_marking_harvested_with_linked_product_posts_inbound_movement(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $season = CropSeason::factory()->create([
            'company_id' => $company->id,
            'field_id' => $field->id,
            'harvested_product_id' => $product->id,
            'status' => 'growing',
        ]);

        $season = app(CropSeasonService::class)->markHarvested($season, [
            'actual_harvest_date' => now()->toDateString(),
            'actual_yield' => 1200,
        ]);

        $this->assertSame('harvested', $season->status);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'harvest_in',
            'reference_type' => 'CROP_SEASON',
            'reference_id' => $season->id,
        ]);
        $this->assertSame(1200.0, app(StockService::class)->available($product->id));
    }

    public function test_marking_harvested_without_linked_product_does_not_touch_stock(): void
    {
        $company = $this->actingCompany();
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $season = CropSeason::factory()->create(['company_id' => $company->id, 'field_id' => $field->id, 'status' => 'growing']);

        $season = app(CropSeasonService::class)->markHarvested($season, [
            'actual_harvest_date' => now()->toDateString(),
            'actual_yield' => 500,
        ]);

        $this->assertSame('harvested', $season->status);
        $this->assertSame(0, \App\Models\StockMovement::query()->count());
    }

    public function test_reopen_reverses_the_inbound_movement(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $season = CropSeason::factory()->create([
            'company_id' => $company->id,
            'field_id' => $field->id,
            'harvested_product_id' => $product->id,
            'status' => 'growing',
        ]);

        $season = app(CropSeasonService::class)->markHarvested($season, [
            'actual_harvest_date' => now()->toDateString(),
            'actual_yield' => 300,
        ]);

        app(CropSeasonService::class)->reopen($season);

        $season->refresh();
        $this->assertSame('growing', $season->status);
        $this->assertNull($season->actual_yield);
        $this->assertSame(0.0, app(StockService::class)->available($product->id));
    }
}
