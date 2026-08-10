<?php

namespace App\Livewire\FinancialEntries;

use App\Exceptions\PeriodLockedException;
use App\Models\Category;
use Carbon\Carbon;
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
use App\Livewire\Concerns\HasPerPageSelector;
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
    use HasPerPageSelector, WithPagination;

    // Aba ativa — controla tanto o filtro da listagem quanto o "type"
    // usado ao criar um novo lançamento.
    #[Url]
    public string $tab = 'expense'; // expense | income | transfer

    // Filtros
    // Período — mesmas 20 opções do sistema legado: os 12 meses (0-11),
    // os 4 trimestres (12-15), os 2 semestres (16-17), "Ano todo" (18) e
    // "Todo período" (19, ignora até o ano). Por padrão abre já filtrado
    // no mês/ano atual (ver mount()).
    #[Url]
    public string $period = '';
    #[Url]
    public string $year = '';
    #[Url]
    public string $status = 'all'; // all | pending | paid
    // Qual data o filtro de mês/ano considera — mesma ideia do "Filtrar
    // por data de mov./vcto./ambas" do sistema legado: vencimento é o que
    // a maioria usa no dia a dia, competência é o que interessa pro DRE.
    #[Url]
    public string $filterDateType = 'due'; // due | movement | both
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
    // Categoria/Contato ficam escondidos por padrão — o legado não tinha
    // esses dois na barra rápida, só os campos acima.
    public bool $showMoreFilters = false;

    // Formulário
    public bool $showForm = false;
    public ?int $editingId = null;

    // Mensagem exibida quando uma ação esbarra no fechamento de período.
    public ?string $lockError = null;

    // Cadastro rápido: cada um desses controla um mini-formulário (só
    // nome) que aparece ao lado do respectivo campo, pra não precisar
    // sair do lançamento pra cadastrar um contato/categoria/centro de
    // custo novo. O registro nasce com needs_review=true, como um lembrete
    // de completar o cadastro depois com calma.
    public bool $showQuickContact = false;
    public string $quickContactName = '';
    public bool $showQuickCategory = false;
    public string $quickCategoryName = '';
    public bool $showQuickCostCenter = false;
    public string $quickCostCenterName = '';

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

    // Só usados em transferência entre contas de moedas diferentes (ver
    // getIsCrossCurrencyTransferProperty()). destination_amount é quanto
    // chega de fato na conta de destino — quando as duas contas são da
    // mesma moeda, nem aparece no formulário, e o saldo assume que chegou
    // o mesmo valor que saiu (comportamento de sempre). fee_amount é a
    // tarifa da operação, debitada a mais da conta de origem.
    #[Validate('nullable|numeric|gt:0')]
    public string $destination_amount = '';

    #[Validate('nullable|numeric|min:0')]
    public string $fee_amount = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|date')]
    public string $due_date = '';

    // Data de competência (regime de competência) — quando o fato gerador
    // aconteceu, usada no DRE. Por padrão acompanha o vencimento (é o caso
    // mais comum: nota e vencimento no mesmo dia), mas pode ser destacada
    // quando a compra/serviço é de um mês e o vencimento cai no seguinte.
    #[Validate('required|date')]
    public string $movement_date = '';

    public bool $movementEqualsDue = true;

    #[Validate('nullable|string|max:255')]
    public string $document_number = '';

    #[Validate('nullable|date')]
    public string $paid_date = '';

    // Parcelamento — só se aplica na criação (não em edição). Gera N
    // lançamentos com vencimento mensal a partir do due_date informado; a
    // competência (movement_date) é a mesma pra todas as parcelas, é a
    // mesma nota/compra só paga em partes.
    public bool $installmentsEnabled = false;

    #[Validate('required_if:installmentsEnabled,true|integer|min:2|max:60')]
    public string $installmentsCount = '2';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);
        $this->due_date = now()->toDateString();
        $this->movement_date = now()->toDateString();
        // Igual ao legado: abre já filtrado no mês e ano atuais, não em
        // "todos" — quem quiser ver tudo escolhe "Todo período" na mão.
        $this->period = $this->period !== '' ? $this->period : (string) (now()->month - 1);
        $this->year = $this->year !== '' ? $this->year : (string) now()->year;
    }

    /**
     * Meses cobertos pela opção de período selecionada (índices 1-12).
     * Null quando o período não restringe por mês (18 = "Ano todo", que
     * ainda filtra pelo ano; 19 = "Todo período" nem chega a chamar isto,
     * ver baseQuery()).
     */
    private function periodMonths(): ?array
    {
        $p = (int) $this->period;

        if ($p >= 0 && $p <= 11) {
            return [$p + 1];
        }

        if ($p >= 12 && $p <= 15) {
            $quarter = $p - 12;

            return [$quarter * 3 + 1, $quarter * 3 + 2, $quarter * 3 + 3];
        }

        if ($p === 16) {
            return range(1, 6);
        }

        if ($p === 17) {
            return range(7, 12);
        }

        return null; // 18 — Ano todo
    }

    /** Mantém a competência acompanhando o vencimento enquanto o "movimento = vencimento" estiver marcado. */
    public function updatedDueDate(string $value): void
    {
        if ($this->movementEqualsDue) {
            $this->movement_date = $value;
        }
    }

    public function updatedMovementEqualsDue(bool $value): void
    {
        if ($value) {
            $this->movement_date = $this->due_date;
        }
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('financial', 'full');
    }

    /**
     * Transferência entre contas de moedas diferentes — só nesse caso o
     * formulário mostra "Valor de destino" e "Tarifa". Contas na mesma
     * moeda continuam com um "Valor" só, como sempre foi.
     */
    public function getIsCrossCurrencyTransferProperty(): bool
    {
        if ($this->tab !== 'transfer' || ! $this->financial_account_id || ! $this->destination_account_id) {
            return false;
        }

        $sourceCurrency = FinancialAccount::query()->find($this->financial_account_id)?->currency_code;
        $destinationCurrency = FinancialAccount::query()->find($this->destination_account_id)?->currency_code;

        return $sourceCurrency && $destinationCurrency && $sourceCurrency !== $destinationCurrency;
    }

    /** Taxa implícita entre o valor de origem e o de destino — só pra mostrar na tela, informativo. */
    public function getTransferExchangeRatePreviewProperty(): ?string
    {
        if (! $this->isCrossCurrencyTransfer || (float) $this->amount <= 0 || $this->destination_amount === '') {
            return null;
        }

        return number_format((float) $this->destination_amount / (float) $this->amount, 6, ',', '.');
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

    public function quickCreateContact(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate(['quickContactName' => ['required', 'string', 'max:255']], [], ['quickContactName' => 'nome']);

        $contact = Contact::query()->create([
            'name' => $data['quickContactName'],
            'is_customer' => $this->tab === 'income',
            'is_supplier' => $this->tab === 'expense',
            'needs_review' => true,
        ]);

        $this->contact_id = $contact->id;
        $this->quickContactName = '';
        $this->showQuickContact = false;
    }

    public function quickCreateCategory(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate(['quickCategoryName' => ['required', 'string', 'max:255']], [], ['quickCategoryName' => 'nome']);

        $category = Category::query()->create([
            'name' => $data['quickCategoryName'],
            'type' => $this->tab === 'income' ? 'income' : 'expense',
            'needs_review' => true,
        ]);

        $this->category_id = $category->id;
        $this->quickCategoryName = '';
        $this->showQuickCategory = false;
    }

    public function quickCreateCostCenter(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate(['quickCostCenterName' => ['required', 'string', 'max:255']], [], ['quickCostCenterName' => 'nome']);

        $costCenter = CostCenter::query()->create([
            'name' => $data['quickCostCenterName'],
            'needs_review' => true,
        ]);

        $this->cost_center_id = $costCenter->id;
        $this->quickCostCenterName = '';
        $this->showQuickCostCenter = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'financial_account_id', 'destination_account_id', 'contact_id',
            'category_id', 'cost_center_id', 'amount', 'description',
            'document_number', 'paid_date', 'editingId',
            'installmentsEnabled', 'destination_amount', 'fee_amount',
            'showQuickContact', 'quickContactName',
            'showQuickCategory', 'quickCategoryName',
            'showQuickCostCenter', 'quickCostCenterName',
        ]);
        $this->currency_code = 'BRL';
        $this->due_date = now()->toDateString();
        $this->movement_date = now()->toDateString();
        $this->movementEqualsDue = true;
        $this->installmentsCount = '2';
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->lockError = null;
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
        $this->destination_amount = $entry->destination_amount !== null ? (string) $entry->destination_amount : '';
        $this->fee_amount = $entry->fee_amount !== null ? (string) $entry->fee_amount : '';
        $this->description = (string) $entry->description;
        $this->document_number = (string) $entry->document_number;
        $this->due_date = $entry->due_date->toDateString();
        $this->movement_date = $entry->movement_date?->toDateString() ?? $entry->due_date->toDateString();
        $this->movementEqualsDue = $this->movement_date === $this->due_date;
        $this->paid_date = $entry->paid_date?->toDateString() ?? '';
        $this->installmentsEnabled = false;
        $this->lockError = null;
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
            'document_number' => ['nullable', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'movement_date' => ['required', 'date'],
            'paid_date' => ['nullable', 'date'],
        ];

        if ($this->tab === 'transfer') {
            $rules['destination_account_id'] = ['required', 'exists:financial_accounts,id', 'different:financial_account_id'];
            $rules['fee_amount'] = ['nullable', 'numeric', 'min:0'];

            if ($this->isCrossCurrencyTransfer) {
                $rules['destination_amount'] = ['required', 'numeric', 'gt:0'];
            }
        } else {
            $rules['contact_id'] = ['nullable', 'exists:contacts,id'];
            $rules['category_id'] = ['nullable', 'exists:categories,id'];
            $rules['cost_center_id'] = ['nullable', 'exists:cost_centers,id'];
        }

        // Parcelamento só faz sentido criando um lançamento novo — editar
        // sempre mexe num registro só.
        $usingInstallments = ! $this->editingId && $this->tab !== 'transfer' && $this->installmentsEnabled;
        if ($usingInstallments) {
            $rules['installmentsCount'] = ['required', 'integer', 'min:2', 'max:60'];
        }

        $data = $this->validate($rules);
        // Campo "Pago em" em branco chega como string vazia, não null — e
        // o MySQL (diferente do SQLite dos testes) rejeita '' numa coluna
        // DATE. Normaliza antes de qualquer outra coisa usar esse valor.
        $data['paid_date'] = $data['paid_date'] ?: null;
        $data['document_number'] = $data['document_number'] ?: null;
        $data['type'] = $this->tab;
        $data['status'] = ! empty($data['paid_date']) ? 'paid' : 'pending';

        if ($this->tab === 'transfer') {
            $data['fee_amount'] = ($data['fee_amount'] ?? '') !== '' ? $data['fee_amount'] : null;

            // Valor de destino e taxa só fazem sentido entre moedas
            // diferentes — numa transferência normal (mesma moeda) ficam
            // nulos, e o saldo assume "chegou o mesmo que saiu" (ver
            // FinancialAccount::currentBalance()).
            if ($this->isCrossCurrencyTransfer && ! empty($data['destination_amount'])) {
                $data['exchange_rate'] = bcdiv((string) $data['destination_amount'], (string) $data['amount'], 6);
            } else {
                $data['destination_amount'] = null;
                $data['exchange_rate'] = null;
            }
        }

        $this->lockError = null;

        try {
            if ($this->editingId) {
                FinancialEntry::query()->findOrFail($this->editingId)->update($data);
            } elseif ($usingInstallments) {
                $this->createInstallments($data, (int) $data['installmentsCount']);
            } else {
                FinancialEntry::query()->create($data);
            }
        } catch (PeriodLockedException $e) {
            $this->lockError = $e->getMessage();

            return;
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Gera N lançamentos mensais a partir de um único formulário — é a
     * mesma compra/nota, só o pagamento é parcelado. A competência
     * (movement_date) fica igual em todas as parcelas (o fato gerador
     * aconteceu uma vez só); o vencimento avança um mês por parcela; e o
     * valor total é dividido pelo número de parcelas, com a última
     * absorvendo o resto de centavos do arredondamento.
     */
    private function createInstallments(array $data, int $count): void
    {
        unset($data['installmentsCount']);

        $totalCents = (int) round(((float) $data['amount']) * 100);
        $baseCents = intdiv($totalCents, $count);
        $remainderCents = $totalCents - ($baseCents * $count);

        $baseDueDate = Carbon::parse($data['due_date']);
        $baseDescription = $data['description'];

        for ($i = 0; $i < $count; $i++) {
            $installmentCents = $baseCents + ($i === $count - 1 ? $remainderCents : 0);

            FinancialEntry::query()->create([
                ...$data,
                'amount' => number_format($installmentCents / 100, 2, '.', ''),
                'due_date' => $baseDueDate->copy()->addMonthsNoOverflow($i)->toDateString(),
                'description' => trim(($baseDescription ?: '').' ('.($i + 1)."/{$count})"),
            ]);
        }
    }

    /**
     * "Baixar" — marca o lançamento como pago/recebido hoje, sem
     * precisar abrir o formulário inteiro (mesmo atalho que existia no
     * sistema antigo).
     */
    public function markAsPaid(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->lockError = null;

        try {
            FinancialEntry::query()->findOrFail($id)->update([
                'status' => 'paid',
                'paid_date' => now()->toDateString(),
            ]);
        } catch (PeriodLockedException $e) {
            $this->lockError = $e->getMessage();
        }
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->lockError = null;

        try {
            FinancialEntry::query()->findOrFail($id)->delete();
        } catch (PeriodLockedException $e) {
            $this->lockError = $e->getMessage();
        }
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

        // "Todo período" (19) ignora data por completo — nem o ano entra
        // no filtro, igual ao legado.
        if ($this->period !== '19' && $this->year !== '') {
            $columns = match ($this->filterDateType) {
                'movement' => ['movement_date'],
                'both' => ['due_date', 'movement_date'],
                default => ['due_date'],
            };
            $months = $this->periodMonths();

            $query->where(function ($q) use ($columns, $months) {
                foreach ($columns as $column) {
                    $q->orWhere(function ($sub) use ($column, $months) {
                        $sub->whereYear($column, $this->year);

                        if ($months !== null) {
                            $sub->where(function ($m) use ($column, $months) {
                                foreach ($months as $mo) {
                                    $m->orWhereMonth($column, $mo);
                                }
                            });
                        }
                    });
                }
            });
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
        $entries = $this->baseQuery()->paginate($this->perPage);

        // Cadastro inativo continua valendo pro que já foi lançado (não
        // some da listagem nem quebra edição), mas não aparece como opção
        // pra um lançamento novo — por isso o orWhere pelo id já
        // selecionado no formulário.
        return view('livewire.financial-entries.index', [
            'entries' => $entries,
            'totalPending' => $this->baseQuery()->where('status', 'pending')->sum('amount'),
            'totalPaid' => $this->baseQuery()->where('status', 'paid')->sum('amount'),
            'accounts' => FinancialAccount::query()
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhereIn('id', array_filter([$this->financial_account_id, $this->destination_account_id, $this->filterAccountId])))
                ->orderBy('name')->get(),
            'categories' => Category::query()
                ->where('type', $this->tab === 'income' ? 'income' : 'expense')
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhereIn('id', array_filter([$this->category_id, $this->filterCategoryId])))
                ->orderBy('name')->get(),
            'costCenters' => CostCenter::query()
                ->where($this->tab === 'income' ? 'applies_to_revenue' : 'applies_to_expense', true)
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhereIn('id', array_filter([$this->cost_center_id, $this->filterCostCenterId])))
                ->orderBy('name')->get(),
            'contacts' => Contact::query()->orderBy('name')->get(),
            'currencies' => Currency::query()->orderBy('code')->get(),
        ]);
    }
}
