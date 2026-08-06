<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompany(): Company
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        return $company;
    }

    public function test_available_sums_inbound_minus_outbound(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $service = app(StockService::class);

        $service->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 10,
        ]);
        $service->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'adjustment_out',
            'movement_date' => now()->toDateString(),
            'quantity' => 3,
        ]);

        $this->assertSame(7.0, $service->available($product->id));
    }

    public function test_outbound_movement_beyond_available_throws(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $service = app(StockService::class);

        $this->expectException(InsufficientStockException::class);

        $service->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'adjustment_out',
            'movement_date' => now()->toDateString(),
            'quantity' => 5,
        ]);
    }

    public function test_transfer_moves_balance_between_locations(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $origin = StockLocation::factory()->create(['company_id' => $company->id]);
        $destination = StockLocation::factory()->create(['company_id' => $company->id]);
        $service = app(StockService::class);

        $service->postMovement([
            'product_id' => $product->id,
            'location_id' => $origin->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 10,
        ]);

        [$out, $in] = $service->transfer([
            'product_id' => $product->id,
            'from_location_id' => $origin->id,
            'to_location_id' => $destination->id,
            'movement_date' => now()->toDateString(),
            'quantity' => 4,
        ]);

        $this->assertSame('transfer_out', $out->movement_type);
        $this->assertSame('transfer_in', $in->movement_type);
        $this->assertNotNull($out->transfer_group);
        $this->assertSame($out->transfer_group, $in->transfer_group);
        $this->assertSame(6.0, $service->available($product->id, $origin->id));
        $this->assertSame(4.0, $service->available($product->id, $destination->id));
        $this->assertSame(10.0, $service->available($product->id)); // consolidado não muda
    }

    public function test_transfer_beyond_available_at_origin_throws(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $origin = StockLocation::factory()->create(['company_id' => $company->id]);
        $destination = StockLocation::factory()->create(['company_id' => $company->id]);
        $service = app(StockService::class);

        $this->expectException(InsufficientStockException::class);

        $service->transfer([
            'product_id' => $product->id,
            'from_location_id' => $origin->id,
            'to_location_id' => $destination->id,
            'movement_date' => now()->toDateString(),
            'quantity' => 1,
        ]);
    }

    public function test_reverse_by_reference_deletes_matching_movements_only(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $service = app(StockService::class);

        $service->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 5,
            'reference_type' => 'TEST_SOURCE',
            'reference_id' => 99,
        ]);
        $service->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 2,
        ]);

        $service->reverseByReference('TEST_SOURCE', 99);

        $this->assertSame(1, StockMovement::query()->count());
        $this->assertSame(2.0, $service->available($product->id));
    }
}
