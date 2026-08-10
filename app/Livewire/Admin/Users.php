<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasPerPageSelector;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela de administração: quem tem acesso à empresa ativa e com qual
 * nível (NONE/READ/FULL) em cada módulo. Só quem tem FULL no módulo
 * 'admin' enxerga isso — é o que controla quem pode dar acesso a quem.
 *
 * Também cobre o acesso de um usuário em OUTRAS empresas que o admin
 * logado administra (painel "Outras empresas" por usuário, mais busca
 * por e-mail pra achar/gerenciar alguém que ainda nem está na empresa
 * ativa) — isso existia antes como uma tela separada (Admin\Access),
 * mas ficou pouco prático desconectada do fluxo normal de gerenciar
 * usuário. Juntar tudo aqui resolve o mesmo problema (usuário com
 * papéis diferentes empresa a empresa) sem obrigar trocar de tela.
 */
#[Layout('layouts.app')]
class Users extends Component
{
    use HasPerPageSelector, WithPagination;

    public bool $showInviteForm = false;

    // Painel "acesso em outras empresas" de um usuário específico.
    public ?int $otherCompaniesUserId = null;
    public string $searchEmail = '';
    public ?string $searchError = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    // Edição de permissões
    public ?int $editingUserId = null;
    public array $levels = [];

    // Edição de dados do usuário (nome/e-mail/senha)
    public ?int $editingDetailsUserId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editPassword = '';
    public string $editPassword_confirmation = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
    }

    private function currentCompany(): Company
    {
        return Auth::user()->currentCompany;
    }

    /**
     * Empresas onde o admin logado tem controle total ("admin" + "full")
     * — só essas podem aparecer/ser editadas no painel "outras
     * empresas". Por padrão exclui a empresa ativa (essa já tem a
     * tabela principal desta tela cuidando dela).
     */
    private function manageableCompanies(bool $excludeCurrent = true)
    {
        $query = Company::query()
            ->whereHas('permissions', function ($q) {
                $q->where('user_id', Auth::id())->where('module', 'admin')->where('level', 'full');
            });

        if ($excludeCurrent) {
            $query->where('id', '!=', $this->currentCompany()->id);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Abre o painel de acesso multi-empresa de um usuário — tanto faz
     * se ele já está na empresa ativa (veio de um botão da lista) ou
     * foi achado pela busca por e-mail (pode ser alguém de fora dela).
     */
    public function openOtherCompaniesPanel(int $userId): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
        abort_if($userId === Auth::id(), 422, 'Use a tela de Perfil pra mexer na sua própria conta.');

        $this->otherCompaniesUserId = $userId;
        $this->searchEmail = '';
        $this->searchError = null;
    }

    public function closeOtherCompaniesPanel(): void
    {
        $this->otherCompaniesUserId = null;
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

        $this->otherCompaniesUserId = $user->id;
        $this->searchEmail = '';
    }

    /**
     * Muda o nível de UM módulo em UMA empresa (que não a ativa) pro
     * usuário do painel "outras empresas" — salva na hora, célula por
     * célula. Mantém o vínculo company_user coerente: entra quando
     * ganha o primeiro acesso qualquer naquela empresa, sai quando
     * perde o último.
     */
    public function setOtherCompanyLevel(int $userId, int $companyId, string $module, string $level): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
        abort_if($userId === Auth::id(), 422, 'Use a tela de Perfil pra mexer na sua própria conta.');
        abort_unless(in_array($module, Permission::MODULES, true), 422);
        abort_unless(in_array($level, Permission::LEVELS, true), 422);

        $company = $this->manageableCompanies(excludeCurrent: false)->firstWhere('id', $companyId);
        abort_unless($company, 403);

        Permission::query()->updateOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId, 'module' => $module],
            ['level' => $level]
        );

        $hasAnyAccess = Permission::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('level', '!=', 'none')
            ->exists();

        if ($hasAnyAccess) {
            $company->users()->syncWithoutDetaching([$userId]);
        } else {
            $company->users()->detach($userId);

            // Se essa era a empresa ativa dele, limpa — senão ele fica
            // "preso" numa empresa que não vê mais no seletor.
            User::query()->where('id', $userId)->where('current_company_id', $companyId)
                ->update(['current_company_id' => null]);
        }
    }

    public function inviteUser(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $data = $this->validate();
        $company = $this->currentCompany();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'current_company_id' => $company->id,
        ]);

        $company->users()->syncWithoutDetaching([$user->id => ['role' => 'member']]);

        // Começa sem acesso a nada — o admin ajusta módulo a módulo
        // logo em seguida, na própria tela (é mais seguro que já
        // nascer com acesso e alguém esquecer de restringir depois).
        foreach (Permission::MODULES as $module) {
            Permission::query()->firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id, 'module' => $module],
                ['level' => 'none']
            );
        }

        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->showInviteForm = false;
    }

    public function editPermissions(int $userId): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $company = $this->currentCompany();

        $this->editingUserId = $userId;
        $this->levels = [];

        foreach (Permission::MODULES as $module) {
            $this->levels[$module] = Permission::query()
                ->where('company_id', $company->id)
                ->where('user_id', $userId)
                ->where('module', $module)
                ->value('level') ?? 'none';
        }
    }

    public function savePermissions(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $this->validate([
            'levels' => ['required', 'array'],
            'levels.*' => ['required', 'in:none,read,full'],
        ]);

        $company = $this->currentCompany();

        foreach ($this->levels as $module => $level) {
            if (! in_array($module, Permission::MODULES, true)) {
                continue;
            }

            Permission::query()->updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $this->editingUserId, 'module' => $module],
                ['level' => $level]
            );
        }

        $this->editingUserId = null;
        $this->levels = [];
    }

    public function cancelEditingPermissions(): void
    {
        $this->editingUserId = null;
        $this->levels = [];
    }

    public function editDetails(int $userId): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $user = User::query()->findOrFail($userId);

        $this->editingDetailsUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPassword = '';
        $this->editPassword_confirmation = '';
    }

    public function saveDetails(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $data = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'unique:users,email,'.$this->editingDetailsUserId],
            // Senha é opcional aqui: só troca se o admin preencher algo.
            'editPassword' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->findOrFail($this->editingDetailsUserId);

        $user->update([
            'name' => $data['editName'],
            'email' => $data['editEmail'],
            ...(! empty($data['editPassword']) ? ['password' => Hash::make($data['editPassword'])] : []),
        ]);

        $this->cancelEditingDetails();
    }

    public function cancelEditingDetails(): void
    {
        $this->reset(['editingDetailsUserId', 'editName', 'editEmail', 'editPassword', 'editPassword_confirmation']);
    }

    public function removeUser(int $userId): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
        abort_if($userId === Auth::id(), 422, 'Você não pode remover a si mesmo da empresa.');

        $company = $this->currentCompany();

        $company->users()->detach($userId);

        // Apaga uma a uma (não ->delete() em massa via query builder) para
        // que o trait Auditable, que depende dos eventos do Eloquent,
        // registre a remoção de cada permissão no log de auditoria.
        Permission::query()
            ->where('company_id', $company->id)
            ->where('user_id', $userId)
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        // Se a empresa removida era a "empresa ativa" desse usuário,
        // limpa — senão ele fica preso numa empresa que não vê mais.
        User::query()->where('id', $userId)->where('current_company_id', $company->id)
            ->update(['current_company_id' => null]);
    }

    public function render()
    {
        $company = $this->currentCompany();

        $users = $company->users()->orderBy('name')->paginate($this->perPage);

        // paginate() já devolve o paginator com a Collection dentro —
        // pra anexar a relação computada em cada User sem perder a
        // paginação, mapeia e regrava a coleção interna em vez de usar
        // ->map() direto (que devolveria uma Collection solta, sem
        // firstItem()/lastItem()/links() etc.).
        $users->setCollection(
            $users->getCollection()->map(function (User $user) use ($company) {
                $user->setRelation('modulePermissions', Permission::query()
                    ->where('company_id', $company->id)
                    ->where('user_id', $user->id)
                    ->pluck('level', 'module'));

                return $user;
            })
        );

        $otherCompanies = $this->manageableCompanies();
        $otherCompaniesUser = $this->otherCompaniesUserId ? User::find($this->otherCompaniesUserId) : null;

        $otherCompaniesLevels = [];
        if ($this->otherCompaniesUserId) {
            $otherCompaniesLevels = Permission::query()
                ->where('user_id', $this->otherCompaniesUserId)
                ->whereIn('company_id', $otherCompanies->pluck('id'))
                ->get()
                ->groupBy('company_id')
                ->map(fn ($rows) => $rows->pluck('level', 'module'));
        }

        return view('livewire.admin.users', [
            'users' => $users,
            'modules' => Permission::MODULES,
            'levels_options' => Permission::LEVELS,
            'otherCompanies' => $otherCompanies,
            'otherCompaniesUser' => $otherCompaniesUser,
            'otherCompaniesLevels' => $otherCompaniesLevels,
        ]);
    }
}
