<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dropdown no topo da tela pra trocar a "empresa ativa" do usuário.
 * Fica visível em todas as telas (incluído no layout de navegação).
 */
class CompanySwitcher extends Component
{
    public function switchTo(int $companyId): void
    {
        $user = Auth::user();

        // Segurança: só deixa trocar pra empresa que o usuário
        // realmente participa (não confia em qualquer id vindo do
        // front-end).
        abort_unless($user->companies()->where('companies.id', $companyId)->exists(), 403);

        $user->update(['current_company_id' => $companyId]);

        // Recarrega a página inteira: mais simples e seguro do que
        // tentar re-renderizar tudo que depende da empresa ativa.
        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: false);
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.company-switcher', [
            'companies' => $user->companies,
            'current' => $user->currentCompany,
        ]);
    }
}
