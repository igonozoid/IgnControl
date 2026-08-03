<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Relatórios cadastrais: listagem simples e imprimível de cada cadastro
 * (contas, categorias, centros de custo, contatos) — pra conferência ou
 * arquivo, sem precisar abrir cada tela de cadastro uma por uma.
 */
#[Layout('layouts.app')]
class Registrations extends Component
{
    #[Url]
    public string $type = 'accounts'; // accounts | categories | cost-centers | contacts

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);
    }

    public function render()
    {
        $rows = match ($this->type) {
            'categories' => Category::query()->orderBy('type')->orderBy('name')->get(),
            'cost-centers' => CostCenter::query()->orderBy('name')->get(),
            'contacts' => Contact::query()->orderBy('name')->get(),
            default => FinancialAccount::query()->orderBy('name')->get(),
        };

        return view('livewire.reports.registrations', [
            'rows' => $rows,
        ]);
    }
}
