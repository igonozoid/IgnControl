<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pedido de venda exige uma moeda válida (FK) — garante que BRL
        // existe antes de cada teste, mesmo padrão dos outros testes
        // financeiros.
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    private function actingCompany(): Company
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        return $company;
    }

    private function itemRow(Product $product, float $quantity = 1, float $unitPrice = 10): array
    {
        return [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }

    public function test_draft_order_does_not_touch_stock_or_financial(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'draft',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
        ], [$this->itemRow($product, 5, 20)]);

        $this->assertSame('draft', $order->status);
        $this->assertNull($order->financial_entry_id);
        $this->assertSame(0, StockMovement::query()->where('reference_type', 'SALE_ORDER')->count());
        $this->assertSame('100.00', number_format($order->total_amount, 2, '.', ''));
    }

    public function test_confirming_a_sale_generates_stock_movement_and_pending_financial_entry(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
            'financial_account_id' => $account->id,
        ], [$this->itemRow($product, 5, 20)]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale_out',
            'reference_type' => 'SALE_ORDER',
            'reference_id' => $order->id,
        ]);
        $this->assertNotNull($order->financial_entry_id);
        $this->assertSame('pending', $order->financialEntry->status);
        $this->assertSame(95.0, app(StockService::class)->available($product->id));
    }

    public function test_settled_sale_generates_paid_financial_entry(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'settled',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
            'financial_account_id' => $account->id,
        ], [$this->itemRow($product, 2, 50)]);

        $this->assertSame('paid', $order->financialEntry->status);
    }

    public function test_donation_never_generates_a_financial_entry_even_when_confirmed(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 10,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'donation',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
            'financial_account_id' => $account->id,
        ], [$this->itemRow($product, 1, 20)]);

        $this->assertNull($order->financial_entry_id);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'donation_out',
        ]);
    }

    public function test_return_type_generates_an_inbound_movement(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'return',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => false,
        ], [$this->itemRow($product, 3, 10)]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'return_in',
            'reference_id' => $order->id,
        ]);
        $this->assertSame(3.0, app(StockService::class)->available($product->id));
    }

    public function test_confirming_a_sale_beyond_available_stock_throws(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->expectException(InsufficientStockException::class);

        app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => false,
        ], [$this->itemRow($product, 5, 20)]);
    }

    public function test_editing_a_confirmed_order_recreates_items_and_movements_without_duplicating(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => false,
        ], [$this->itemRow($product, 5, 20)]);

        app(SalesOrderService::class)->upsert($order, [
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => false,
        ], [$this->itemRow($product, 8, 20)]);

        $this->assertSame(1, StockMovement::query()->where('reference_type', 'SALE_ORDER')->where('reference_id', $order->id)->count());
        $this->assertSame(1, $order->fresh()->items()->count());
        $this->assertSame(92.0, app(StockService::class)->available($product->id));
    }

    public function test_cancel_reverses_stock_and_cancels_financial_entry(): void
    {
        $company = $this->actingCompany();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 100,
        ]);

        $order = app(SalesOrderService::class)->upsert(null, [
            'sale_type' => 'sale',
            'status' => 'settled',
            'sale_date' => now()->toDateString(),
            'currency_code' => 'BRL',
            'generate_financial_entry' => true,
            'financial_account_id' => $account->id,
        ], [$this->itemRow($product, 5, 20)]);

        app(SalesOrderService::class)->cancel($order);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('canceled', $order->financialEntry->status);
        $this->assertSame(100.0, app(StockService::class)->available($product->id));
    }
}
