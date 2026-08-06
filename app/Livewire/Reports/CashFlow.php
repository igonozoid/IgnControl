<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fluxo de caixa realizado: saldo inicial (tudo que já foi pago antes do
 * período) + movimentos pagos dia a dia + saldo corrente + saldo final.
 *
 * Transferências entre contas da própria empresa têm efeito líquido zero
 * no caixa consolidado da empresa (sai de uma conta, entra em outra), por
 * isso não entram nas somas de entrada/saída aqui.
 */
#[Layout('layouts.app')]
class CashFlow extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);

        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    private function paidQuery()
    {
        return FinancialEntry::query()
            ->where('status', 'paid')
            ->whereIn('type', ['income', 'expense']);
    }

    public function render()
    {
        $openingBalance = (clone $this->paidQuery())
            // date(paid_date) normaliza a comparação: o Eloquent grava
            // colunas `date` com hora embutida, o que deixa comparação de
            // string pura de data pouco confiável no SQLite.
            ->where(DB::raw('date(paid_date)'), '<', $this->from)
            ->get()
            ->sum(fn ($entry) => $entry->type === 'income' ? $entry->amount : -$entry->amount);

        $rows = (clone $this->paidQuery())
            ->whereBetween(DB::raw('date(paid_date)'), [$this->from, $this->to])
            ->selectRaw('paid_date, type, sum(amount) as total')
            ->groupBy('paid_date', 'type')
            ->orderBy('paid_date')
            ->get()
            ->groupBy('paid_date')
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

        $running = $openingBalance;
        $days = $rows->map(function ($row) use (&$running) {
            $running += $row['net'];
            $row['balance'] = $running;

            return $row;
        });

        $closingBalance = $running;

        return view('livewire.reports.cash-flow', [
            'openingBalance' => $openingBalance,
            'days' => $days,
            'closingBalance' => $closingBalance,
        ]);
    }
}
