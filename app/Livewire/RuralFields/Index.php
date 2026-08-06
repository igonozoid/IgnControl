<?php

namespace App\Livewire\RuralFields;

use App\Models\RuralField;
use App\Models\RuralProperty;
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
    public string $filterProperty = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|integer|exists:rural_properties,id')]
    public string $property_id = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $display_label = 'Talhão';

    #[Validate('required|in:general,crop,pasture,orchard,apiary')]
    public string $field_type = 'crop';

    #[Validate('nullable|numeric|min:0')]
    public string $size_area = '';

    #[Validate('required|string|max:20')]
    public string $size_unit = 'ha';

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
        $this->reset(['property_id', 'name', 'size_area', 'notes', 'editingId']);
        $this->display_label = 'Talhão';
        $this->field_type = 'crop';
        $this->size_unit = 'ha';
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $field = RuralField::query()->findOrFail($id);

        $this->editingId = $field->id;
        $this->property_id = (string) $field->property_id;
        $this->name = $field->name;
        $this->display_label = $field->display_label;
        $this->field_type = $field->field_type;
        $this->size_area = (string) $field->size_area;
        $this->size_unit = $field->size_unit;
        $this->notes = (string) $field->notes;
        $this->is_active = $field->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['size_area'] = $data['size_area'] !== '' ? $data['size_area'] : null;
        $data['notes'] = $data['notes'] ?: null;

        if ($this->editingId) {
            RuralField::query()->findOrFail($this->editingId)->update($data);
        } else {
            RuralField::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['property_id', 'name', 'size_area', 'notes', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            RuralField::query()->findOrFail($id)->delete();
        } catch (QueryException) {
            $this->deleteError = 'Não é possível excluir este talhão: já existe safra ou atividade vinculada a ele. Marque como inativo em vez de excluir.';
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['property_id', 'name', 'size_area', 'notes', 'editingId']);
    }

    public function render()
    {
        $fields = RuralField::query()
            ->with('property')
            ->when($this->filterProperty !== '', fn ($q) => $q->where('property_id', $this->filterProperty))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.rural-fields.index', [
            'fields' => $fields,
            'properties' => RuralProperty::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
