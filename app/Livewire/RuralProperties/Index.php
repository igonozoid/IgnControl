<?php

namespace App\Livewire\RuralProperties;

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
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    #[Validate('nullable|string|max:255')]
    public string $state = '';

    #[Validate('nullable|string|max:255')]
    public string $country = '';

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
        $this->reset(['name', 'city', 'state', 'country', 'notes', 'editingId']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $property = RuralProperty::query()->findOrFail($id);

        $this->editingId = $property->id;
        $this->name = $property->name;
        $this->city = (string) $property->city;
        $this->state = (string) $property->state;
        $this->country = (string) $property->country;
        $this->notes = (string) $property->notes;
        $this->is_active = $property->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['city'] = $data['city'] ?: null;
        $data['state'] = $data['state'] ?: null;
        $data['country'] = $data['country'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        if ($this->editingId) {
            RuralProperty::query()->findOrFail($this->editingId)->update($data);
        } else {
            RuralProperty::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'city', 'state', 'country', 'notes', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            RuralProperty::query()->findOrFail($id)->delete();
        } catch (QueryException) {
            $this->deleteError = 'Não é possível excluir esta propriedade: já existe talhão cadastrado nela. Marque como inativa em vez de excluir.';
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'city', 'state', 'country', 'notes', 'editingId']);
    }

    public function render()
    {
        $properties = RuralProperty::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->withCount('fields')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.rural-properties.index', [
            'properties' => $properties,
        ]);
    }
}
