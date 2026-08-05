<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Filtros
    #[Url]
    public string $search = '';
    #[Url]
    public string $filterType = '';
    #[Url]
    public bool $onlyNeedsReview = false;

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:income,expense')]
    public string $type = 'expense';

    #[Validate('nullable|exists:categories,id')]
    public ?int $parent_id = null;

    #[Validate('nullable|in:01 RECEITA BRUTA,02 DEDUCOES DA RECEITA,03 CUSTOS DOS SERVICOS/VENDAS,04 DESPESAS OPERACIONAIS,05 RESULTADO FINANCEIRO,06 OUTRAS RECEITAS/DESPESAS')]
    public string $dre_group = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('financial', 'full');
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->reset(['name', 'type', 'parent_id', 'dre_group', 'editingId']);
        $this->type = 'expense';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $category = Category::query()->findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->parent_id = $category->parent_id;
        $this->dre_group = (string) $category->dre_group;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['dre_group'] = $data['dre_group'] ?: null;

        if ($this->editingId) {
            $data['needs_review'] = false;
            Category::query()->findOrFail($this->editingId)->update($data);
        } else {
            Category::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'type', 'parent_id', 'dre_group', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Category::query()->findOrFail($id)->delete();
    }

    public function markReviewed(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Category::query()->findOrFail($id)->update(['needs_review' => false]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'type', 'parent_id', 'dre_group', 'editingId']);
    }

    public function render()
    {
        $categories = Category::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->onlyNeedsReview, fn ($q) => $q->where('needs_review', true))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.categories.index', [
            'categories' => $categories,
            'parentOptions' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
