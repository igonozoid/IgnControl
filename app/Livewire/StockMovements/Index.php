<?php

namespace App\Livewire\StockMovements;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela única de Estoque pro dia a dia: saldo por produto, histórico de
 * movimentações (filtrável) e as duas ações manuais — lançar uma
 * movimentação avulsa (entrada/ajuste/perda) ou transferir entre locais.
 * Tudo passa pelo StockService, nunca grava direto no model.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $filterProductId = '';

    #[Url]
    public string $filterLocationId = '';

    #[Url]
    public string $filterType = '';

    public bool $showMovementForm = false;
    public bool $showTransferForm = false;

    // Movimentação manual
    #[Validate('required|integer|exists:products,id')]
    public string $product_id = '';

    #[Validate('nullable|integer|exists:stock_locations,id')]
    public string $location_id = '';

    #[Validate('required|in:manual_in,adjustment_in,adjustment_out,loss_out')]
    public string $movement_type = 'manual_in';

    #[Validate('required|date')]
    public string $movement_date = '';

    #[Validate('required|numeric|gt:0')]
    public string $quantity = '';

    #[Validate('nullable|numeric|min:0')]
    public string $unit_cost = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    // Transferência
    #[Validate('required|integer|exists:products,id')]
    public string $transfer_product_id = '';

    #[Validate('required|integer|exists:stock_locations,id|different:transfer_to_location_id')]
    public string $transfer_from_location_id = '';

    #[Validate('required|integer|exists:stock_locations,id')]
    public string $transfer_to_location_id = '';

    #[Validate('required|date')]
    public string $transfer_date = '';

    #[Validate('required|numeric|gt:0')]
    public string $transfer_quantity = '';

    #[Validate('nullable|string')]
    public string $transfer_notes = '';

    public ?string $formError = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('inventory', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('inventory', 'full');
    }

    public function openMovementForm(): void
    {
        abort_unless($this->canWrite, 403);
        $this->reset(['product_id', 'location_id', 'quantity', 'unit_cost', 'notes']);
        $this->movement_type = 'manual_in';
        $this->movement_date = now()->toDateString();
        $this->formError = null;
        $this->showMovementForm = true;
    }

    public function saveMovement(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'product_id' => 'required|integer|exists:products,id',
            'location_id' => 'nullable|integer|exists:stock_locations,id',
            'movement_type' => 'required|in:manual_in,adjustment_in,adjustment_out,loss_out',
            'movement_date' => 'required|date',
            'quantity' => 'required|numeric|gt:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->formError = null;

        try {
            app(StockService::class)->postMovement([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'] ?: null,
                'movement_type' => $data['movement_type'],
                'movement_date' => $data['movement_date'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?: 0,
                'notes' => $data['notes'] ?: null,
                'created_by_user_id' => Auth::id(),
            ]);
        } catch (InsufficientStockException $e) {
            $this->formError = $e->getMessage();

            return;
        }

        $this->showMovementForm = false;
        $this->reset(['product_id', 'location_id', 'quantity', 'unit_cost', 'notes']);
    }

    public function openTransferForm(): void
    {
        abort_unless($this->canWrite, 403);
        $this->reset(['transfer_product_id', 'transfer_from_location_id', 'transfer_to_location_id', 'transfer_quantity', 'transfer_notes']);
        $this->transfer_date = now()->toDateString();
        $this->formError = null;
        $this->showTransferForm = true;
    }

    public function saveTransfer(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'transfer_product_id' => 'required|integer|exists:products,id',
            'transfer_from_location_id' => 'required|integer|exists:stock_locations,id|different:transfer_to_location_id',
            'transfer_to_location_id' => 'required|integer|exists:stock_locations,id',
            'transfer_date' => 'required|date',
            'transfer_quantity' => 'required|numeric|gt:0',
            'transfer_notes' => 'nullable|string',
        ]);

        $this->formError = null;

        try {
            app(StockService::class)->transfer([
                'product_id' => $data['transfer_product_id'],
                'from_location_id' => $data['transfer_from_location_id'],
                'to_location_id' => $data['transfer_to_location_id'],
                'movement_date' => $data['transfer_date'],
                'quantity' => $data['transfer_quantity'],
                'notes' => $data['transfer_notes'] ?: null,
                'created_by_user_id' => Auth::id(),
            ]);
        } catch (InsufficientStockException $e) {
            $this->formError = $e->getMessage();

            return;
        }

        $this->showTransferForm = false;
        $this->reset(['transfer_product_id', 'transfer_from_location_id', 'transfer_to_location_id', 'transfer_quantity', 'transfer_notes']);
    }

    public function cancel(): void
    {
        $this->showMovementForm = false;
        $this->showTransferForm = false;
        $this->formError = null;
    }

    public function render()
    {
        $movements = StockMovement::query()
            ->with(['product', 'location'])
            ->when($this->filterProductId !== '', fn ($q) => $q->where('product_id', $this->filterProductId))
            ->when($this->filterLocationId !== '', fn ($q) => $q->where('location_id', $this->filterLocationId))
            ->when($this->filterType !== '', fn ($q) => $q->where('movement_type', $this->filterType))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.stock-movements.index', [
            'movements' => $movements,
            'balance' => app(StockService::class)->balanceByProduct(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => StockLocation::query()->where('is_active', true)->orderBy('name')->get(),
            'allTypes' => StockMovement::MANUAL_TYPES + StockMovement::SYSTEM_TYPES + StockMovement::TRANSFER_TYPES,
        ]);
    }
}
