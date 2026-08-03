<?php

namespace App\Livewire\FinancialEntries;

use App\Models\Category;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela de Lançamentos Financeiros — inspirada no layout do sistema
 * legado (IgnControl em Python): abas Despesas/Receitas/Transferências,
 * barra de filtros, listagem colorida por situação e um botão "Baixar"
 * pra marcar como pago/recebido sem precisar abrir o formulário de
 * edição inteiro.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Aba ativa — controla tanto o filtro da listagem quanto o "type"
    // usado ao criar um novo lançamento.
    #[Url]
    public string $tab = 'expense'; // expense | income | transfer

    // Filtros
    #[Url]
    public string $month = '';
    #[Url]
    public string $year = '';
    #[Url]
    public string $status = 'all'; // all | pending | paid
    #[Url]
    public ?int $filterAccountId = null;
    #[Url]
    public ?int $filterCategoryId = null;
    #[Url]
    public ?int $filterCostCenterId = null;
    #[Url]
    public ?int $filterContactId = null;
    #[Url]
    public string $search = '';

    // Formulário
    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|exists:financial_accounts,id')]
    public ?int $financial_account_id = null;

    #[Validate('nullable|exists:financial_accounts,id|different:financial_account_id')]
    public ?int $destination_account_id = null;

    #[Validate('nullable|exists:contacts,id')]
    public ?int $contact_id = null;

    #[Validate('nullable|exists:categories,id')]
    public ?int $category_id = null;

    #[Validate('nullable|exists:cost_centers,id')]
    public ?int $cost_center_id = null;

    #[Validate('required|string|size:3|exists:currencies,code')]
    public string $currency_code = 'BRL';

    #[Validate('required|numeric|gt:0')]
    public string $amount = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|date')]
    public string $due_date = '';

    #[Validate('nullable|date')]
    public string $paid_date = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);
        $this->due_date = now()->toDateString();
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('financial', 'full');
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    /**
     * Situação "visual" de um lançamento, pro selo colorido na tabela
     * (mesmo padrão de cores do sistema antigo: vermelho = atrasado,
     * verde = pendente no prazo, cinza = já pago/cancelado).
     */
    public function statusFor(FinancialEntry $entry): string
    {
        if ($entry->status === 'paid') {
            return 'paid';
        }

        if ($entry->status === 'canceled') {
            return 'canceled';
        }

        return $entry->due_date->isPast() ? 'overdue' : 'pending';
    }

    private function resetForm(): void
    {
        $this->reset([
            'financial_account_id', 'destination_account_id', 'contact_id',
            'category_id', 'cost_center_id', 'amount', 'description',
            'paid_date', 'editingId',
        ]);
        $this->currency_code = 'BRL';
        $this->due_date = now()->toDateString();
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $entry = FinancialEntry::query()->findOrFail($id);

        $this->editingId = $entry->id;
        $this->tab = $entry->type;
        $this->financial_account_id = $entry->financial_account_id;
        $this->destination_account_id = $entry->destination_account_id;
        $this->contact_id = $entry->contact_id;
        $this->category_id = $entry->category_id;
        $this->cost_center_id = $entry->cost_center_id;
        $this->currency_code = $entry->currency_code;
        $this->amount = (string) $entry->amount;
        $this->description = (string) $entry->description;
        $this->due_date = $entry->due_date->toDateString();
        $this->paid_date = $entry->paid_date?->toDateString() ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $rules = [
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'paid_date' => ['nullable', 'date'],
        ];

        if ($this->tab === 'transfer') {
            $rules['destination_account_id'] = ['required', 'exists:financial_accounts,id', 'different:financial_account_id'];
        } else {
            $rules['contact_id'] = ['nullable', 'exists:contacts,id'];
            $rules['category_id'] = ['nullable', 'exists:categories,id'];
            $rules['cost_center_id'] = ['nullable', 'exists:cost_centers,id'];
        }

        $data = $this->validate($rules);
        // Campo "Pago em" em branco chega como string vazia, não null — e
        // o MySQL (diferente do SQLite dos testes) rejeita '' numa coluna
        // DATE. Normaliza antes de qualquer outra coisa usar esse valor.
        $data['paid_date'] = $data['paid_date'] ?: null;
        $data['type'] = $this->tab;
        $data['status'] = ! empty($data['paid_date']) ? 'paid' : 'pending';

        if ($this->editingId) {
            FinancialEntry::query()->findOrFail($this->editingId)->update($data);
        } else {
            FinancialEntry::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * "Baixar" — marca o lançamento como pago/recebido hoje, sem
     * precisar abrir o formulário inteiro (mesmo atalho que existia no
     * sistema antigo).
     */
    public function markAsPaid(int $id): void
    {
        abort_unless($this->canWrite, 403);

        FinancialEntry::query()->findOrFail($id)->update([
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        FinancialEntry::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function baseQuery()
    {
        $query = FinancialEntry::query()
            ->with(['financialAccount', 'destinationAccount', 'contact', 'category', 'costCenter'])
            ->where('type', $this->tab);

        if ($this->month !== '' && $this->year !== '') {
            $query->whereYear('due_date', $this->year)->whereMonth('due_date', $this->month);
        } elseif ($this->year !== '') {
            $query->whereYear('due_date', $this->year);
        }

        if ($this->status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->status === 'paid') {
            $query->where('status', 'paid');
        }

        if ($this->filterAccountId) {
            $query->where(function ($q) {
                $q->where('financial_account_id', $this->filterAccountId)
                    ->orWhere('destination_account_id', $this->filterAccountId);
            });
        }

        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        if ($this->filterCostCenterId) {
            $query->where('cost_center_id', $this->filterCostCenterId);
        }

        if ($this->filterContactId) {
            $query->where('contact_id', $this->filterContactId);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
            });
        }

        return $query->orderBy('due_date');
    }

    public function render()
    {
        $entries = $this->baseQuery()->paginate(20);

        return view('livewire.financial-entries.index', [
            'entries' => $entries,
            'totalPending' => $this->baseQuery()->where('status', 'pending')->sum('amount'),
            'totalPaid' => $this->baseQuery()->where('status', 'paid')->sum('amount'),
            'accounts' => FinancialAccount::query()->orderBy('name')->get(),
            'categories' => Category::query()->where('type', $this->tab === 'income' ? 'income' : 'expense')->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->orderBy('name')->get(),
            'contacts' => Contact::query()->orderBy('name')->get(),
            'currencies' => Currency::query()->orderBy('code')->get(),
        ]);
    }
}
