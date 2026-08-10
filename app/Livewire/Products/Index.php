<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
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
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterType = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('nullable|string|max:64')]
    public string $sku = '';

    #[Validate('nullable|string|max:64')]
    public string $barcode = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $short_name = '';

    #[Validate('required|in:product,service,input,gift')]
    public string $product_type = 'product';

    #[Validate('nullable|integer|exists:product_categories,id')]
    public ?int $category_id = null;

    #[Validate('required|string|max:16')]
    public string $unit_code = 'UN';

    #[Validate('nullable|numeric|min:0')]
    public string $default_sale_price = '';

    #[Validate('nullable|numeric|min:0')]
    public string $default_cost = '';

    #[Validate('boolean')]
    public bool $controls_stock = true;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('nullable|string')]
    public string $notes = '';

    public ?string $deleteError = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('inventory', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('inventory', 'full');
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

        $product = Product::query()->findOrFail($id);

        $this->editingId = $product->id;
        $this->sku = (string) $product->sku;
        $this->barcode = (string) $product->barcode;
        $this->name = $product->name;
        $this->short_name = (string) $product->short_name;
        $this->product_type = $product->product_type;
        $this->category_id = $product->category_id;
        $this->unit_code = $product->unit_code;
        $this->default_sale_price = (string) $product->default_sale_price;
        $this->default_cost = (string) $product->default_cost;
        $this->controls_stock = $product->controls_stock;
        $this->is_active = $product->is_active;
        $this->notes = (string) $product->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['sku'] = $data['sku'] !== '' ? $data['sku'] : null;
        $data['barcode'] = $data['barcode'] !== '' ? $data['barcode'] : null;
        $data['default_sale_price'] = $data['default_sale_price'] !== '' ? $data['default_sale_price'] : 0;
        $data['default_cost'] = $data['default_cost'] !== '' ? $data['default_cost'] : 0;

        if ($this->editingId) {
            Product::query()->findOrFail($this->editingId)->update($data);
        } else {
            Product::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            Product::query()->findOrFail($id)->delete();
        } catch (QueryException) {
            $this->deleteError = 'Não é possível excluir este produto: já existe movimentação de estoque registrada pra ele. Marque como inativo em vez de excluir.';
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'sku', 'barcode', 'name', 'short_name', 'category_id',
            'default_sale_price', 'default_cost', 'notes', 'editingId',
        ]);
        $this->product_type = 'product';
        $this->unit_code = 'UN';
        $this->controls_stock = true;
        $this->is_active = true;
    }

    public function render()
    {
        $products = Product::query()
            ->with('category')
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->when($this->filterType !== '', fn ($q) => $q->where('product_type', $this->filterType))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => ProductCategory::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', array_filter([$this->category_id])))
                ->orderBy('name')->get(),
        ]);
    }
}
