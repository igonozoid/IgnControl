<?php

namespace App\Livewire\RuralAssets;

use App\Models\RuralAsset;
use App\Models\RuralField;
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
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('nullable|integer|exists:rural_fields,id')]
    public string $field_id = '';

    #[Validate('required|in:general,machinery,herd,hive,irrigation')]
    public string $asset_type = 'machinery';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $code = '';

    #[Validate('nullable|numeric|min:0')]
    public string $quantity = '';

    #[Validate('required|string|max:20')]
    public string $unit_code = 'UN';

    #[Validate('nullable|date')]
    public string $started_at = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    public ?string $deleteError = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('rural', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('rural', 'full');
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->reset(['field_id', 'name', 'code', 'quantity', 'started_at', 'notes', 'editingId']);
        $this->asset_type = 'machinery';
        $this->unit_code = 'UN';
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $asset = RuralAsset::query()->findOrFail($id);

        $this->editingId = $asset->id;
        $this->field_id = (string) $asset->field_id;
        $this->asset_type = $asset->asset_type;
        $this->name = $asset->name;
        $this->code = (string) $asset->code;
        $this->quantity = (string) $asset->quantity;
        $this->unit_code = $asset->unit_code;
        $this->started_at = $asset->started_at?->toDateString() ?? '';
        $this->notes = (string) $asset->notes;
        $this->is_active = $asset->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['field_id'] = $data['field_id'] ?: null;
        $data['code'] = $data['code'] ?: null;
        $data['quantity'] = $data['quantity'] !== '' ? $data['quantity'] : null;
        $data['started_at'] = $data['started_at'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        if ($this->editingId) {
            RuralAsset::query()->findOrFail($this->editingId)->update($data);
        } else {
            RuralAsset::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['field_id', 'name', 'code', 'quantity', 'started_at', 'notes', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            RuralAsset::query()->findOrFail($id)->delete();
        } catch (QueryException) {
            $this->deleteError = 'Não é possível excluir este ativo: já existe atividade vinculada a ele. Marque como inativo em vez de excluir.';
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['field_id', 'name', 'code', 'quantity', 'started_at', 'notes', 'editingId']);
    }

    public function render()
    {
        $assets = RuralAsset::query()
            ->with('field')
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.rural-assets.index', [
            'assets' => $assets,
            'fields' => RuralField::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
