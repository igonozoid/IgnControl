<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Fechamento de período: define até quando os lançamentos financeiros da
 * empresa ativa ficam travados (não dá pra criar/editar/excluir nada com
 * vencimento até essa data). É o mesmo mecanismo do sistema antigo, pra
 * garantir que um mês já conferido/fechado não seja mexido sem querer.
 *
 * Só quem tem acesso FULL no módulo 'admin' vê essa tela — mudar a data
 * de fechamento é uma decisão administrativa, não operacional.
 */
#[Layout('layouts.app')]
class PeriodLock extends Component
{
    #[Validate('nullable|date')]
    public string $locked_through = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $this->locked_through = $this->currentCompany()->locked_through?->toDateString() ?? '';
    }

    private function currentCompany(): Company
    {
        return Auth::user()->currentCompany;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $data = $this->validate();

        $this->currentCompany()->update([
            'locked_through' => $data['locked_through'] ?: null,
        ]);

        session()->flash('status', 'Fechamento de período atualizado.');
    }

    public function render()
    {
        return view('livewire.admin.period-lock', [
            'company' => $this->currentCompany(),
        ]);
    }
}
