<?php

namespace App\Livewire\ProductCategories;

use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('boolean')]
    public bool $is_active = true;

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
        $this->reset(['name', 'description', 'editingId']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $category = ProductCategory::query()->findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->is_active = $category->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();

        if ($this->editingId) {
            ProductCategory::query()->findOrFail($this->editingId)->update($data);
        } else {
            ProductCategory::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'description', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        ProductCategory::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'description', 'editingId']);
    }

    public function render()
    {
        $categories = ProductCategory::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.product-categories.index', [
            'categories' => $categories,
        ]);
    }
}
