<?php

namespace App\Livewire\CostCenters;

use App\Models\CostCenter;
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

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public string $code = '';

    #[Validate('boolean')]
    public bool $is_active = true;

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
        $this->reset(['name', 'code', 'editingId']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $costCenter = CostCenter::query()->findOrFail($id);

        $this->editingId = $costCenter->id;
        $this->name = $costCenter->name;
        $this->code = (string) $costCenter->code;
        $this->is_active = $costCenter->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();

        if ($this->editingId) {
            CostCenter::query()->findOrFail($this->editingId)->update($data);
        } else {
            CostCenter::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'code', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        CostCenter::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'code', 'editingId']);
    }

    public function render()
    {
        $costCenters = CostCenter::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.cost-centers.index', [
            'costCenters' => $costCenters,
        ]);
    }
}
