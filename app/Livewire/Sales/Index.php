<?php

namespace App\Livewire\Sales;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Livewire\Concerns\HasPerPageSelector;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use HasPerPageSelector, WithPagination;

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterSaleType = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public ?string $formError = null;

    // Cabeçalho
    #[Validate('nullable|integer|exists:contacts,id')]
    public string $contact_id = '';

    #[Validate('required|in:sale,donation,bonus,return')]
    public string $sale_type = 'sale';

    #[Validate('required|in:draft,confirmed,settled')]
    public string $status = 'draft';

    #[Validate('required|date')]
    public string $sale_date = '';

    #[Validate('nullable|date')]
    public string $due_date = '';

    #[Validate('required|string|size:3')]
    public string $currency_code = 'BRL';

    #[Validate('boolean')]
    public bool $generate_financial_entry = true;

    #[Validate('nullable|integer|exists:financial_accounts,id')]
    public string $financial_account_id = '';

    #[Validate('nullable|integer|exists:categories,id')]
    public string $category_id = '';

    #[Validate('nullable|integer|exists:cost_centers,id')]
    public string $cost_center_id = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    // Itens — array de linhas, mesmo padrão de listas simples usado em
    // Contatos (referências) e Lançamentos (parcelas).
    public array $itemRows = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('sales', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('sales', 'full');
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $order = SalesOrder::query()->with('items')->findOrFail($id);

        // Cancelado é terminal — não dá pra editar de volta pra vida
        // (reabrir seria só criar um pedido novo).
        abort_if($order->status === 'cancelled', 403);

        $this->editingId = $order->id;
        $this->contact_id = (string) $order->contact_id;
        $this->sale_type = $order->sale_type;
        $this->status = $order->status;
        $this->sale_date = $order->sale_date->toDateString();
        $this->due_date = $order->due_date?->toDateString() ?? '';
        $this->currency_code = $order->currency_code;
        $this->generate_financial_entry = $order->generate_financial_entry;
        $this->financial_account_id = (string) $order->financial_account_id;
        $this->category_id = (string) $order->category_id;
        $this->cost_center_id = (string) $order->cost_center_id;
        $this->notes = (string) $order->notes;
        $this->itemRows = $order->items->map(fn ($item) => [
            'product_id' => (string) $item->product_id,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
            'discount_amount' => (string) $item->discount_amount,
            'tax_rate_percent' => (string) $item->tax_rate_percent,
        ])->all();
        $this->formError = null;
        $this->showForm = true;
    }

    public function addItemRow(): void
    {
        $this->itemRows[] = ['product_id' => '', 'quantity' => '1', 'unit_price' => '0', 'discount_amount' => '0', 'tax_rate_percent' => '0'];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->itemRows[$index]);
        $this->itemRows = array_values($this->itemRows);
    }

    /** Prévia dos totais em tempo real, mesma matemática do serviço. */
    public function getPreviewTotalsProperty(): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;

        foreach ($this->itemRows as $row) {
            $lineSubtotal = (float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0);
            $lineDiscount = (float) ($row['discount_amount'] ?? 0);
            $lineTax = round(($lineSubtotal - $lineDiscount) * (float) ($row['tax_rate_percent'] ?? 0) / 100, 2);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $subtotal - $discount + $tax,
        ];
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        unset($data['notes']);
        $data['notes'] = $this->notes !== '' ? $this->notes : null;
        $data['contact_id'] = $data['contact_id'] ?: null;
        $data['due_date'] = $data['due_date'] ?: null;
        $data['financial_account_id'] = $data['financial_account_id'] ?: null;
        $data['category_id'] = $data['category_id'] ?: null;
        $data['cost_center_id'] = $data['cost_center_id'] ?: null;

        if ($data['generate_financial_entry'] && $data['status'] !== 'draft' && $data['sale_type'] === 'sale' && ! $data['financial_account_id']) {
            $this->addError('financial_account_id', 'Escolha a conta que vai receber o valor da venda, ou desmarque "gerar lançamento financeiro".');

            return;
        }

        if (empty($this->itemRows)) {
            $this->formError = 'Adicione ao menos um item ao pedido.';

            return;
        }

        $this->formError = null;

        try {
            $order = $this->editingId ? SalesOrder::query()->findOrFail($this->editingId) : null;

            app(SalesOrderService::class)->upsert($order, $data, $this->itemRows);
        } catch (InsufficientStockException $e) {
            $this->formError = $e->getMessage();

            return;
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function cancelOrder(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $order = SalesOrder::query()->findOrFail($id);

        try {
            app(SalesOrderService::class)->cancel($order);
        } catch (InsufficientStockException $e) {
            $this->formError = $e->getMessage();
        }
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $order = SalesOrder::query()->findOrFail($id);

        // Um pedido que já saiu do rascunho tem histórico de estoque/
        // financeiro atrelado — cancela em vez de excluir, pra não
        // sumir com o rastro. Só rascunho pode ser excluído de fato.
        abort_unless($order->status === 'draft', 403);

        $order->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['contact_id', 'due_date', 'financial_account_id', 'category_id', 'cost_center_id', 'notes', 'editingId', 'itemRows']);
        $this->sale_type = 'sale';
        $this->status = 'draft';
        $this->sale_date = now()->toDateString();
        $this->currency_code = 'BRL';
        $this->generate_financial_entry = true;
        $this->formError = null;
    }

    public function render()
    {
        $orders = SalesOrder::query()
            ->with(['contact', 'items'])
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSaleType !== '', fn ($q) => $q->where('sale_type', $this->filterSaleType))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.sales.index', [
            'orders' => $orders,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'financialAccounts' => FinancialAccount::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::query()->where('type', 'income')->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->where('is_active', true)->where('applies_to_revenue', true)->orderBy('name')->get(),
        ]);
    }
}
