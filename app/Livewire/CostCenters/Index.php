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

    #[Url]
    public bool $onlyNeedsReview = false;

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public string $code = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('boolean')]
    public bool $applies_to_expense = true;

    #[Validate('boolean')]
    public bool $applies_to_revenue = true;

    #[Validate('nullable|numeric|min:0')]
    public string $expense_budget = '';

    #[Validate('nullable|numeric|min:0')]
    public string $revenue_projection = '';

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
        $this->reset(['name', 'code', 'editingId', 'expense_budget', 'revenue_projection']);
        $this->is_active = true;
        $this->applies_to_expense = true;
        $this->applies_to_revenue = true;
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
        $this->applies_to_expense = $costCenter->applies_to_expense;
        $this->applies_to_revenue = $costCenter->applies_to_revenue;
        $this->expense_budget = $costCenter->expense_budget !== null ? (string) $costCenter->expense_budget : '';
        $this->revenue_projection = $costCenter->revenue_projection !== null ? (string) $costCenter->revenue_projection : '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['expense_budget'] = $data['expense_budget'] !== '' ? $data['expense_budget'] : null;
        $data['revenue_projection'] = $data['revenue_projection'] !== '' ? $data['revenue_projection'] : null;

        if ($this->editingId) {
            $data['needs_review'] = false;
            CostCenter::query()->findOrFail($this->editingId)->update($data);
        } else {
            CostCenter::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'code', 'editingId', 'expense_budget', 'revenue_projection']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        CostCenter::query()->findOrFail($id)->delete();
    }

    public function markReviewed(int $id): void
    {
        abort_unless($this->canWrite, 403);
        CostCenter::query()->findOrFail($id)->update(['needs_review' => false]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'code', 'editingId', 'expense_budget', 'revenue_projection']);
    }

    public function render()
    {
        $costCenters = CostCenter::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->when($this->onlyNeedsReview, fn ($q) => $q->where('needs_review', true))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.cost-centers.index', [
            'costCenters' => $costCenters,
        ]);
    }
}
