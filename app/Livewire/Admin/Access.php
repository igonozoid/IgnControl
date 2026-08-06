<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tela de administração: acesso de UM usuário através de VÁRIAS
 * empresas — o complemento de Admin\Users (que é o inverso: TODOS os
 * usuários de UMA empresa). Existe porque um mesmo usuário pode ter
 * papéis bem diferentes empresa a empresa (full em uma, leitura em
 * outra, nada numa terceira, só um módulo específico numa quarta), e
 * a tela por-empresa obriga a trocar de empresa ativa pra cada ajuste.
 *
 * Escopo de segurança: só aparecem aqui as empresas em que o próprio
 * admin logado tem 'admin'+'full' — não dá pra conceder acesso a uma
 * empresa que você mesmo não administra. E a própria conta do admin
 * logado nunca aparece na lista de usuários editáveis (mesma trava de
 * Admin\Users::removeUser — evita se autoexcluir sem querer).
 */
#[Layout('layouts.app')]
class Access extends Component
{
    #[Url]
    public ?int $selectedUserId = null;

    public string $searchEmail = '';

    public ?string $searchError = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
    }

    /**
     * Empresas onde o admin logado tem controle total — só essas podem
     * aparecer/ser editadas nessa tela.
     */
    private function manageableCompanies()
    {
        return Company::query()
            ->whereHas('permissions', function ($q) {
                $q->where('user_id', Auth::id())->where('module', 'admin')->where('level', 'full');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Usuários que já participam de pelo menos uma das empresas que o
     * admin gerencia — é o universo natural de "gente que eu já lido".
     * Pra dar acesso a alguém totalmente novo a essas empresas, usa a
     * busca por e-mail (searchByEmail), que aceita qualquer usuário do
     * sistema.
     */
    private function manageableUsers()
    {
        $companyIds = $this->manageableCompanies()->pluck('id');

        return User::query()
            ->whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();
    }

    public function selectUser(int $userId): void
    {
        abort_if($userId === Auth::id(), 422, 'Use a tela de Perfil pra mexer na sua própria conta.');

        $this->selectedUserId = $userId;
        $this->searchEmail = '';
        $this->searchError = null;
    }

    public function searchByEmail(): void
    {
        $this->searchError = null;
        $email = trim($this->searchEmail);

        if ($email === '') {
            return;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->searchError = 'Nenhum usuário encontrado com esse e-mail.';

            return;
        }

        if ($user->id === Auth::id()) {
            $this->searchError = 'Use a tela de Perfil pra mexer na sua própria conta.';

            return;
        }

        $this->selectedUserId = $user->id;
        $this->searchEmail = '';
    }

    public function clearSelection(): void
    {
        $this->selectedUserId = null;
    }

    /**
     * Muda o nível de UM módulo em UMA empresa pro usuário selecionado
     * — salva na hora (célula por célula), sem precisar de um botão
     * "salvar" separado. Também mantém o vínculo company_user
     * coerente: entra quando o usuário ganha o primeiro acesso
     * qualquer, sai quando perde o último.
     */
    public function setLevel(int $companyId, string $module, string $level): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
        abort_unless($this->selectedUserId && $this->selectedUserId !== Auth::id(), 403);
        abort_unless(in_array($module, Permission::MODULES, true), 422);
        abort_unless(in_array($level, Permission::LEVELS, true), 422);

        $company = $this->manageableCompanies()->firstWhere('id', $companyId);
        abort_unless($company, 403);

        Permission::query()->updateOrCreate(
            ['company_id' => $companyId, 'user_id' => $this->selectedUserId, 'module' => $module],
            ['level' => $level]
        );

        $hasAnyAccess = Permission::query()
            ->where('company_id', $companyId)
            ->where('user_id', $this->selectedUserId)
            ->where('level', '!=', 'none')
            ->exists();

        if ($hasAnyAccess) {
            $company->users()->syncWithoutDetaching([$this->selectedUserId]);
        } else {
            $company->users()->detach($this->selectedUserId);

            // Se essa era a empresa ativa dele, limpa — senão ele fica
            // "preso" numa empresa que não vê mais no seletor.
            User::query()->where('id', $this->selectedUserId)->where('current_company_id', $companyId)
                ->update(['current_company_id' => null]);
        }
    }

    public function render()
    {
        $companies = $this->manageableCompanies();

        $levelsByCompany = [];
        if ($this->selectedUserId) {
            $levelsByCompany = Permission::query()
                ->where('user_id', $this->selectedUserId)
                ->whereIn('company_id', $companies->pluck('id'))
                ->get()
                ->groupBy('company_id')
                ->map(fn ($rows) => $rows->pluck('level', 'module'));
        }

        return view('livewire.admin.access', [
            'companies' => $companies,
            'users' => $this->manageableUsers(),
            'modules' => Permission::MODULES,
            'levelsOptions' => Permission::LEVELS,
            'levelsByCompany' => $levelsByCompany,
            'selectedUser' => $this->selectedUserId ? User::find($this->selectedUserId) : null,
        ]);
    }
}
