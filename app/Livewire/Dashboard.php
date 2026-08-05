<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Painel inicial: um resumo rápido de "como estão as coisas" sem
 * precisar abrir cada tela. Cada bloco só aparece se o usuário tem
 * acesso de leitura ao módulo correspondente — quem só vê Contatos,
 * por exemplo, não precisa (nem deve) ver saldo de caixa aqui.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        // Ver comentário equivalente em App\Livewire\Tasks\Index::render().
        Carbon::setLocale('pt_BR');

        $user = Auth::user();
        $canSeeFinancial = $user->hasModuleAccess('financial', 'read');
        $canSeeAgenda = $user->hasModuleAccess('agenda', 'read');
        $canReview = $user->hasModuleAccess('financial', 'full') || $user->hasModuleAccess('contacts', 'full');

        $data = [
            'canSeeFinancial' => $canSeeFinancial,
            'canSeeAgenda' => $canSeeAgenda,
            'canReview' => $canReview,
        ];

        if ($canSeeFinancial) {
            $data = array_merge($data, $this->financialData());
        }

        if ($canSeeAgenda) {
            $data['upcomingTasks'] = Task::query()
                ->where('status', 'pending')
                ->orderByRaw('due_date is null, due_date')
                ->limit(5)
                ->get();
        }

        if ($canReview) {
            $data['needsReviewCount'] = Contact::query()->where('needs_review', true)->count()
                + Category::query()->where('needs_review', true)->count()
                + CostCenter::query()->where('needs_review', true)->count();
        }

        return view('livewire.dashboard', $data);
    }

    private function financialData(): array
    {
        $cashBalance = FinancialAccount::query()->where('is_active', true)->get()
            ->reduce(fn ($carry, $account) => bcadd($carry, $account->currentBalance(), 4), '0');

        $pendingIncome = FinancialEntry::query()->where('type', 'income')->where('status', 'pending');
        $pendingExpense = FinancialEntry::query()->where('type', 'expense')->where('status', 'pending');

        $receivablesTotal = (clone $pendingIncome)->sum('amount');
        $receivablesOverdue = (clone $pendingIncome)->where('due_date', '<', now()->toDateString())->sum('amount');
        $payablesTotal = (clone $pendingExpense)->sum('amount');
        $payablesOverdue = (clone $pendingExpense)->where('due_date', '<', now()->toDateString())->sum('amount');

        $monthIncome = FinancialEntry::query()->where('type', 'income')->where('status', 'paid')
            ->whereBetween('paid_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount');
        $monthExpense = FinancialEntry::query()->where('type', 'expense')->where('status', 'paid')
            ->whereBetween('paid_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount');

        // Últimos 6 meses (incluindo o atual), receita x despesa paga —
        // pra dar uma noção de tendência de um jeito visual, sem precisar
        // abrir o DRE.
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            $income = FinancialEntry::query()->where('type', 'income')->where('status', 'paid')
                ->whereYear('paid_date', $month->year)->whereMonth('paid_date', $month->month)->sum('amount');
            $expense = FinancialEntry::query()->where('type', 'expense')->where('status', 'paid')
                ->whereYear('paid_date', $month->year)->whereMonth('paid_date', $month->month)->sum('amount');

            return [
                'label' => $month->translatedFormat('M/y'),
                'income' => (float) $income,
                'expense' => (float) $expense,
            ];
        });

        $maxMonthValue = max(1, $months->flatMap(fn ($m) => [$m['income'], $m['expense']])->max());

        $upcomingEntries = FinancialEntry::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('status', 'pending')
            ->where('due_date', '<=', now()->addDays(14)->toDateString())
            ->with('contact')
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        return [
            'cashBalance' => $cashBalance,
            'receivablesTotal' => $receivablesTotal,
            'receivablesOverdue' => $receivablesOverdue,
            'payablesTotal' => $payablesTotal,
            'payablesOverdue' => $payablesOverdue,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'months' => $months,
            'maxMonthValue' => $maxMonthValue,
            'upcomingEntries' => $upcomingEntries,
        ];
    }
}
