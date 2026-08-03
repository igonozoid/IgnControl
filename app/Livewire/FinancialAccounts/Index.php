<?php

namespace App\Livewire\FinancialAccounts;

use App\Models\Currency;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela piloto do padrão que as próximas telas de negócio vão seguir:
 * listagem + formulário (criar/editar) no mesmo componente, com um
 * "modo" (isEditing) controlando o que aparece.
 *
 * #[Layout] aponta pro layout clássico que o Breeze gerou
 * (resources/views/layouts/app.blade.php) — o padrão do Livewire é
 * procurar em components.layouts.app, que não existe neste projeto.
 */
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
    public string $filterCurrency = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:cash,bank')]
    public string $type = 'cash';

    #[Validate('required|string|size:3|exists:currencies,code')]
    public string $currency_code = 'BRL';

    #[Validate('numeric')]
    public string $opening_balance = '0';

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
        $this->reset(['name', 'type', 'currency_code', 'opening_balance', 'editingId']);
        $this->type = 'cash';
        $this->currency_code = 'BRL';
        $this->opening_balance = '0';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $account = FinancialAccount::query()->findOrFail($id);

        $this->editingId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->currency_code = $account->currency_code;
        $this->opening_balance = (string) $account->opening_balance;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();

        if ($this->editingId) {
            FinancialAccount::query()->findOrFail($this->editingId)->update($data);
        } else {
            FinancialAccount::query()->create($data);
        }

        $this->showForm = false;
        $this->reset(['name', 'type', 'currency_code', 'opening_balance', 'editingId']);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        FinancialAccount::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['name', 'type', 'currency_code', 'opening_balance', 'editingId']);
    }

    public function render()
    {
        $accounts = FinancialAccount::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterCurrency !== '', fn ($q) => $q->where('currency_code', $this->filterCurrency))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.financial-accounts.index', [
            'accounts' => $accounts,
            'currencies' => Currency::query()->orderBy('code')->get(),
        ]);
    }
}
