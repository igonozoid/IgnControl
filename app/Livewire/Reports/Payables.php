<?php

namespace App\Livewire\Reports;

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Contas a pagar por fornecedor: despesas em aberto (pendentes, ainda não
 * pagas nem canceladas), agrupadas por contato, com total e destaque de
 * atrasados — mesma lógica de status usada na tela de Lançamentos.
 */
#[Layout('layouts.app')]
class Payables extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);
    }

    public function render()
    {
        $entries = FinancialEntry::query()
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->with('contact')
            ->orderBy('due_date')
            ->get();

        $byContact = $entries->groupBy(fn ($entry) => $entry->contact->name ?? 'Sem contato')
            ->map(function ($group) {
                return [
                    'entries' => $group,
                    'total' => $group->sum('amount'),
                    'overdue' => $group->sum(fn ($entry) => $entry->due_date->isPast() ? $entry->amount : 0),
                ];
            })
            ->sortByDesc(fn ($group) => $group['total']);

        return view('livewire.reports.payables', [
            'byContact' => $byContact,
            'grandTotal' => $entries->sum('amount'),
        ]);
    }
}
