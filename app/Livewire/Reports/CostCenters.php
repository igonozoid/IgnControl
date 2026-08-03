<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Despesas/receitas por centro de custo: totais de receita e despesa
 * (todo lançamento não cancelado, pela data de vencimento) agrupados por
 * centro de custo, num período.
 */
#[Layout('layouts.app')]
class CostCenters extends Component
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

    public function render()
    {
        $entries = FinancialEntry::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('status', '!=', 'canceled')
            ->whereBetween('due_date', [$this->from, $this->to])
            ->with('costCenter')
            ->get();

        $byCostCenter = $entries
            ->groupBy(fn ($entry) => $entry->costCenter->name ?? 'Sem centro de custo')
            ->map(function ($group) {
                $income = $group->where('type', 'income')->sum('amount');
                $expense = $group->where('type', 'expense')->sum('amount');

                return [
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense,
                ];
            })
            ->sortByDesc(fn ($group) => $group['income'] + $group['expense']);

        return view('livewire.reports.cost-centers', [
            'byCostCenter' => $byCostCenter,
            'totalIncome' => $entries->where('type', 'income')->sum('amount'),
            'totalExpense' => $entries->where('type', 'expense')->sum('amount'),
        ]);
    }
}
