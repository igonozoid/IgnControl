<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * DRE gerencial — 6 seções convencionais (Receita Bruta, Deduções da
 * Receita, Custos, Despesas Operacionais, Resultado Financeiro, Outras
 * Receitas/Despesas), mesma estrutura usada no sistema legado.
 *
 * Duas coisas que o legado já acertava e que replicamos aqui:
 * 1. Filtra/agrupa por movement_date (regime de competência) — não por
 *    due_date (vencimento). Uma despesa de janeiro com vencimento em
 *    fevereiro precisa cair no DRE de janeiro, senão o resultado do mês
 *    nunca fecha certo.
 * 2. Cada categoria pode ter uma seção do DRE explícita (Category::dre_group);
 *    quando não tem, infere pela palavra-chave do nome (fallback), pra não
 *    obrigar cadastro prévio de tudo antes do relatório funcionar.
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

    /** Mesma heurística por palavra-chave do sistema legado (reports.py::_infer_dre_section). */
    public static function inferSection(string $type, ?string $dreGroup, string $categoryName): string
    {
        $explicit = strtoupper(trim((string) $dreGroup));
        if ($explicit !== '') {
            return $explicit;
        }

        $text = strtoupper($categoryName);

        if ($type === 'income') {
            foreach (['DEVOLU', 'ABATIMENTO', 'DESCONTO', 'IMPOSTO', 'SIMPLES', 'ISS', 'ICMS', 'PIS', 'COFINS'] as $term) {
                if (str_contains($text, $term)) {
                    return '02 DEDUCOES DA RECEITA';
                }
            }

            return '01 RECEITA BRUTA';
        }

        foreach (['SIMPLES', 'ISS', 'ICMS', 'PIS', 'COFINS', 'IMPOSTO', 'TRIBUTO', 'TAXA'] as $term) {
            if (str_contains($text, $term)) {
                return '02 DEDUCOES DA RECEITA';
            }
        }

        foreach (['CMV', 'CUSTO', 'INSUMO', 'MATERIAL', 'PRODUCAO', 'PRODUÇÃO'] as $term) {
            if (str_contains($text, $term)) {
                return '03 CUSTOS DOS SERVICOS/VENDAS';
            }
        }

        foreach (['JUROS', 'TARIFA', 'IOF', 'EMPRESTIMO', 'EMPRÉSTIMO', 'FINANCEIR'] as $term) {
            if (str_contains($text, $term)) {
                return '05 RESULTADO FINANCEIRO';
            }
        }

        foreach (['VENDA DE ATIVO', 'INDENIZ', 'MULTA', 'OUTRA'] as $term) {
            if (str_contains($text, $term)) {
                return '06 OUTRAS RECEITAS/DESPESAS';
            }
        }

        return '04 DESPESAS OPERACIONAIS';
    }

    public function render()
    {
        $entries = FinancialEntry::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('status', '!=', 'canceled')
            ->whereBetween('movement_date', [$this->from, $this->to])
            ->with('category')
            ->get();

        $sectionOrder = array_keys(Category::DRE_GROUPS);

        $buckets = [];
        foreach ($sectionOrder as $section) {
            $buckets[$section] = ['income' => [], 'expense' => []];
        }

        foreach ($entries as $entry) {
            $categoryName = $entry->category?->name ?? 'Sem categoria';
            $section = self::inferSection($entry->type, $entry->category?->dre_group, $categoryName);

            if (! isset($buckets[$section])) {
                $buckets[$section] = ['income' => [], 'expense' => []];
                $sectionOrder[] = $section;
            }

            $key = $entry->type === 'income' ? 'income' : 'expense';
            $buckets[$section][$key][$categoryName] = ($buckets[$section][$key][$categoryName] ?? 0) + (float) $entry->amount;
        }

        $totalIncome = 0.0;
        $totalExpense = 0.0;
        $sections = [];

        foreach ($sectionOrder as $section) {
            $income = collect($buckets[$section]['income'] ?? [])->sortKeys();
            $expense = collect($buckets[$section]['expense'] ?? [])->sortKeys();

            if ($income->isEmpty() && $expense->isEmpty()) {
                continue;
            }

            $incomeTotal = $income->sum();
            $expenseTotal = $expense->sum();
            $totalIncome += $incomeTotal;
            $totalExpense += $expenseTotal;

            $sections[] = [
                'label' => Category::DRE_GROUPS[$section] ?? $section,
                'income' => $income,
                'expense' => $expense,
                'income_total' => $incomeTotal,
                'expense_total' => $expenseTotal,
                'total' => $incomeTotal - $expenseTotal,
            ];
        }

        return view('livewire.reports.dre', [
            'sections' => $sections,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'result' => $totalIncome - $totalExpense,
        ]);
    }
}
