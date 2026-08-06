<?php

namespace Tests\Feature;

use App\Livewire\Sales\Index;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesScreenTest extends TestCase
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

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'sales',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_sales_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_full_access_user_can_create_a_draft_order(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('sale_type', 'sale')
            ->set('status', 'draft')
            ->set('sale_date', now()->toDateString())
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '3')
            ->set('itemRows.0.unit_price', '15')
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('sales_orders', [
            'company_id' => $company->id,
            'status' => 'draft',
            'total_amount' => 45,
        ]);
    }

    public function test_confirming_a_sale_generates_stock_and_financial_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 50,
        ]);

        Livewire::test(Index::class)
            ->call('create')
            ->set('status', 'confirmed')
            ->set('sale_date', now()->toDateString())
            ->set('financial_account_id', (string) $account->id)
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '2')
            ->set('itemRows.0.unit_price', '25')
            ->call('save')
            ->assertSet('showForm', false);

        $order = SalesOrder::query()->firstOrFail();
        $this->assertNotNull($order->financial_entry_id);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale_out',
            'reference_id' => $order->id,
        ]);
    }

    public function test_insufficient_stock_shows_a_form_error_instead_of_saving(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('status', 'confirmed')
            ->set('sale_date', now()->toDateString())
            ->set('generate_financial_entry', false)
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '10')
            ->set('itemRows.0.unit_price', '5')
            ->call('save')
            ->assertSet('showForm', true)
            ->assertSee('Estoque insuficiente');

        $this->assertSame(0, SalesOrder::query()->count());
    }

    public function test_cancelling_an_order_reverses_stock_and_cancels_financial_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);
        app(StockService::class)->postMovement([
            'product_id' => $product->id,
            'movement_type' => 'manual_in',
            'movement_date' => now()->toDateString(),
            'quantity' => 50,
        ]);

        Livewire::test(Index::class)
            ->call('create')
            ->set('status', 'settled')
            ->set('sale_date', now()->toDateString())
            ->set('financial_account_id', (string) $account->id)
            ->call('addItemRow')
            ->set('itemRows.0.product_id', (string) $product->id)
            ->set('itemRows.0.quantity', '2')
            ->set('itemRows.0.unit_price', '25')
            ->call('save');

        $order = SalesOrder::query()->firstOrFail();

        Livewire::test(Index::class)->call('cancelOrder', $order->id);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('canceled', $order->financialEntry->status);
        $this->assertSame(50.0, app(StockService::class)->available($product->id));
    }

    public function test_cannot_delete_a_confirmed_order(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $order = SalesOrder::factory()->create(['company_id' => $company->id, 'status' => 'confirmed']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $order->id)->assertForbidden();

        $this->assertDatabaseHas('sales_orders', ['id' => $order->id]);
    }

    public function test_can_delete_a_draft_order(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $order = SalesOrder::factory()->create(['company_id' => $company->id, 'status' => 'draft']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $order->id);

        $this->assertDatabaseMissing('sales_orders', ['id' => $order->id]);
    }
}
