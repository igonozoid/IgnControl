<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Relatório analítico: lista, linha a linha, todo lançamento de receita
 * ou despesa num período (independente de já ter sido pago ou não),
 * com totais — pra conferência detalhada, diferente do DRE (que só
 * mostra totais por categoria).
 */
#[Layout('layouts.app')]
class Analytical extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $type = 'all'; // all | income | expense

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
            ->when($this->type !== 'all', fn ($q) => $q->where('type', $this->type))
            // date(due_date) normaliza a comparação: o Eloquent grava
            // colunas `date` com hora embutida, o que deixa whereBetween
            // com string pura de data pouco confiável no SQLite.
            ->whereBetween(DB::raw('date(due_date)'), [$this->from, $this->to])
            ->with(['category', 'costCenter', 'contact'])
            ->orderBy('due_date')
            ->get();

        return view('livewire.reports.analytical', [
            'entries' => $entries,
            'totalIncome' => $entries->where('type', 'income')->sum('amount'),
            'totalExpense' => $entries->where('type', 'expense')->sum('amount'),
        ]);
    }
}
