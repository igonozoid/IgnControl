<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Tela de administração: quem tem acesso à empresa ativa e com qual
 * nível (NONE/READ/FULL) em cada módulo. Só quem tem FULL no módulo
 * 'admin' enxerga isso — é o que controla quem pode dar acesso a quem.
 */
#[Layout('layouts.app')]
class Users extends Component
{
    public bool $showInviteForm = false;

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

        $users = $company->users()->orderBy('name')->get()->map(function (User $user) use ($company) {
            $user->setRelation('modulePermissions', Permission::query()
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->pluck('level', 'module'));

            return $user;
        });

        return view('livewire.admin.users', [
            'users' => $users,
            'modules' => Permission::MODULES,
            'levels_options' => Permission::LEVELS,
        ]);
    }
}
