<?php

namespace App\Livewire\Currencies;

use App\Livewire\Concerns\HasPerPageSelector;
use App\Models\Currency;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cadastro de moedas — não existia tela nenhuma pra isso antes, moeda só
 * entrava via seed/migração. O cadastro em si é igual ao do legado:
 * código (ISO 4217), nome, símbolo, casas decimais e ativa/inativa.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use HasPerPageSelector, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?string $editingCode = null;

    #[Validate('required|string|size:3|alpha')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:8')]
    public string $symbol = '';

    #[Validate('required|integer|min:0|max:6')]
    public string $decimals = '2';

    #[Validate('boolean')]
    public bool $is_active = true;

    public ?string $deleteError = null;

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
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $code): void
    {
        abort_unless($this->canWrite, 403);

        $currency = Currency::query()->findOrFail($code);

        $this->editingCode = $currency->code;
        $this->code = $currency->code;
        $this->name = $currency->name;
        $this->symbol = $currency->symbol;
        $this->decimals = (string) $currency->decimals;
        $this->is_active = $currency->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['code'] = strtoupper($data['code']);

        if ($this->editingCode) {
            // Código é a chave primária — não dá pra trocar depois de
            // criado (quebraria as contas/lançamentos que já referenciam
            // esse code). O campo fica travado na view quando editando.
            $data['code'] = $this->editingCode;
            Currency::query()->findOrFail($this->editingCode)->update($data);
        } else {
            Currency::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(string $code): void
    {
        abort_unless($this->canWrite, 403);

        $this->deleteError = null;

        try {
            Currency::query()->findOrFail($code)->delete();
        } catch (QueryException) {
            // FK restrictOnDelete em financial_accounts.currency_code —
            // já existe conta usando essa moeda.
            $this->deleteError = "Não é possível excluir a moeda \"{$code}\": já existe conta financeira usando ela. Marque como inativa em vez de excluir.";
        }
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['code', 'name', 'symbol', 'editingCode']);
        $this->decimals = '2';
        $this->is_active = true;
    }

    public function render()
    {
        $currencies = Currency::query()
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('code')
            ->paginate($this->perPage);

        return view('livewire.currencies.index', [
            'currencies' => $currencies,
        ]);
    }
}
