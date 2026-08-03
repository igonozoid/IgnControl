<?php

namespace App\Livewire\Reports;

use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Extrato de uma conta financeira: todo movimento PAGO que passou por
 * ela (entrada, saída, ou lado de uma transferência), em ordem, com
 * saldo corrente — como um extrato bancário.
 */
#[Layout('layouts.app')]
class AccountStatement extends Component
{
    #[Url]
    public ?int $accountId = null;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);

        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
        $this->accountId = $this->accountId ?: FinancialAccount::query()->orderBy('name')->value('id');
    }

    /**
     * Todo lançamento pago que toca essa conta, de um jeito ou de outro:
     * receita/despesa lançada nela, ou transferência de/para ela.
     */
    private function touchingQuery(FinancialAccount $account)
    {
        return FinancialEntry::query()
            ->where('status', 'paid')
            ->where(function ($q) use ($account) {
                $q->where('financial_account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id);
            });
    }

    /**
     * Efeito no saldo dessa conta específica (positivo = entrou, negativo
     * = saiu), considerando de que lado da transferência ela está.
     */
    private function signedAmount(FinancialEntry $entry, int $accountId): string
    {
        if ($entry->type === 'income') {
            return $entry->amount;
        }

        if ($entry->type === 'expense') {
            return bcmul($entry->amount, -1, 4);
        }

        // Transferência: entrando nessa conta soma, saindo dela subtrai.
        return $entry->destination_account_id === $accountId
            ? $entry->amount
            : bcmul($entry->amount, -1, 4);
    }

    public function render()
    {
        $account = FinancialAccount::query()->find($this->accountId);

        if (! $account) {
            return view('livewire.reports.account-statement', [
                'account' => null,
                'accounts' => FinancialAccount::query()->orderBy('name')->get(),
                'openingBalance' => 0,
                'rows' => collect(),
                'closingBalance' => 0,
            ]);
        }

        $openingBalance = (clone $this->touchingQuery($account))
            ->where('paid_date', '<', $this->from)
            ->get()
            ->sum(fn ($entry) => $this->signedAmount($entry, $account->id));

        $entries = (clone $this->touchingQuery($account))
            ->whereBetween('paid_date', [$this->from, $this->to])
            ->with(['contact', 'category', 'financialAccount', 'destinationAccount'])
            ->orderBy('paid_date')
            ->orderBy('id')
            ->get();

        $running = $openingBalance;
        $rows = $entries->map(function (FinancialEntry $entry) use (&$running, $account) {
            $signed = $this->signedAmount($entry, $account->id);
            $running = bcadd($running, $signed, 4);

            return [
                'entry' => $entry,
                'signed' => $signed,
                'balance' => $running,
            ];
        });

        return view('livewire.reports.account-statement', [
            'account' => $account,
            'accounts' => FinancialAccount::query()->orderBy('name')->get(),
            'openingBalance' => $openingBalance,
            'rows' => $rows,
            'closingBalance' => $running,
        ]);
    }
}
