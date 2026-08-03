<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * DRE simplificada: soma de receitas por categoria menos soma de
 * despesas por categoria, no período. Considera todo lançamento não
 * cancelado pela data de vencimento (regime de competência), não só o
 * que já foi pago — é a leitura usual de uma DRE.
 */
#[Layout('layouts.app')]
class Dre extends Component
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

    private function groupByCategory($entries)
    {
        return $entries
            ->groupBy(fn ($entry) => $entry->category->name ?? 'Sem categoria')
            ->map(fn ($group) => ['total' => $group->sum('amount')])
            ->sortByDesc('total');
    }

    public function render()
    {
        $entries = FinancialEntry::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('status', '!=', 'canceled')
            ->whereBetween('due_date', [$this->from, $this->to])
            ->with('category')
            ->get();

        $income = $this->groupByCategory($entries->where('type', 'income'));
        $expense = $this->groupByCategory($entries->where('type', 'expense'));

        $totalIncome = $entries->where('type', 'income')->sum('amount');
        $totalExpense = $entries->where('type', 'expense')->sum('amount');

        return view('livewire.reports.dre', [
            'income' => $income,
            'expense' => $expense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'result' => $totalIncome - $totalExpense,
        ]);
    }
}
