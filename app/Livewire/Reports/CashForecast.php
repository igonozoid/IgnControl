<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Previsão de caixa: parte do saldo realizado atual (tudo que já foi
 * pago até hoje) e projeta pra frente somando os lançamentos ainda
 * PENDENTES por data de vencimento — "se tudo for pago na data prevista,
 * como fica o caixa".
 */
#[Layout('layouts.app')]
class CashForecast extends Component
{
    #[Url]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);

        $this->to = $this->to ?: now()->addDays(30)->toDateString();
    }

    public function render()
    {
        $currentBalance = FinancialEntry::query()
            ->where('status', 'paid')
            ->whereIn('type', ['income', 'expense'])
            ->get()
            ->sum(fn ($entry) => $entry->type === 'income' ? $entry->amount : -$entry->amount);

        $rows = FinancialEntry::query()
            ->where('status', 'pending')
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('due_date', [now()->toDateString(), $this->to])
            ->selectRaw('due_date, type, sum(amount) as total')
            ->groupBy('due_date', 'type')
            ->orderBy('due_date')
            ->get()
            ->groupBy('due_date')
            ->map(function ($entries, $date) {
                $income = optional($entries->firstWhere('type', 'income'))->total ?? 0;
                $expense = optional($entries->firstWhere('type', 'expense'))->total ?? 0;

                return [
                    'date' => $date,
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense,
                ];
            })
            ->values();

        $running = $currentBalance;
        $days = $rows->map(function ($row) use (&$running) {
            $running += $row['net'];
            $row['balance'] = $running;

            return $row;
        });

        // Pior saldo projetado no período — o ponto que mais importa pra
        // decidir se vai faltar dinheiro em algum momento.
        $lowestBalance = $days->isEmpty() ? $currentBalance : min($days->min('balance'), $currentBalance);

        return view('livewire.reports.cash-forecast', [
            'currentBalance' => $currentBalance,
            'days' => $days,
            'projectedBalance' => $running,
            'lowestBalance' => $lowestBalance,
        ]);
    }
}
