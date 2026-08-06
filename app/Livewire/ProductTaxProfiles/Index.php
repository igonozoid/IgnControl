<?php

namespace App\Livewire\ProductTaxProfiles;

use App\Models\ProductTaxProfile;
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

    #[Validate('required|in:rate,fixed,exempt')]
    public string $tax_mode = 'rate';

    #[Validate('nullable|numeric|min:0|max:100')]
    public string $default_rate_percent = '0';

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('boolean')]
    public bool $is_active = true;

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
        $this->reset(['name', 'notes', 'editingId']);
        $this->tax_mode = 'rate';
        $this->default_rate_percent = '0';
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $profile = ProductTaxProfile::query()->findOrFail($id);

        $this->editingId = $profile->id;
        $this->name = $profile->name;
        $this->tax_mode = $profile->tax_mode;
        $this->default_rate_percent = (string) $profile->default_rate_percent;
        $this->notes = (string) $profile->notes;
        $this->is_active = $profile->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['default_rate_percent'] = $data['default_rate_percent'] !== '' ? $data['default_rate_percent'] : 0;

        if ($this->editingId) {
            ProductTaxProfile::query()->findOrFail($this->editingId)->update($data);
        } else {
            ProductTaxProfile::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'notes', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        // Produtos/itens de venda que já usavam esse perfil só perdem a
        // referência (nullOnDelete) — não há histórico pra preservar
        // aqui como existe em Moedas/Produtos/Locais.
        ProductTaxProfile::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'notes', 'editingId']);
    }

    public function render()
    {
        $profiles = ProductTaxProfile::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.product-tax-profiles.index', [
            'profiles' => $profiles,
        ]);
    }
}
