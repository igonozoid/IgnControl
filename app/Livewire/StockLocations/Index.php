<?php

namespace App\Livewire\StockLocations;

use App\Models\StockLocation;
use Illuminate\Database\QueryException;
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

    #[Validate('required|in:warehouse,store,field,office,internal_use')]
    public string $location_type = 'warehouse';

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('boolean')]
    public bool $is_active = true;

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
        $this->reset(['name', 'notes', 'editingId']);
        $this->location_type = 'warehouse';
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $location = StockLocation::query()->findOrFail($id);

        $this->editingId = $location->id;
        $this->name = $location->name;
        $this->location_type = $location->location_type;
        $this->notes = (string) $location->notes;
        $this->is_active = $location->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();

        if ($this->editingId) {
            StockLocation::query()->findOrFail($this->editingId)->update($data);
        } else {
            StockLocation::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'notes', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            StockLocation::query()->findOrFail($id)->delete();
        } catch (QueryException) {
            $this->deleteError = 'Não é possível excluir este local: já existe movimentação de estoque registrada nele. Marque como inativo em vez de excluir.';
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'notes', 'editingId']);
    }

    public function render()
    {
        $locations = StockLocation::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.stock-locations.index', [
            'locations' => $locations,
        ]);
    }
}
