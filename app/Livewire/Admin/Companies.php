<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Tela de administração: dados cadastrais das empresas que o usuário
 * participa (razão social, CNPJ, moeda base, ativa/inativa) e criação
 * de novas empresas.
 *
 * `locked_through` (fechamento de período) não entra aqui de propósito
 * — já tem tela própria em Admin\PeriodLock, não faz sentido duplicar.
 */
#[Layout('layouts.app')]
class Companies extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $legal_name = '';

    #[Validate('nullable|string|max:32')]
    public string $tax_id = '';

    #[Validate('required|string|size:3|exists:currencies,code')]
    public string $base_currency_code = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $companyId): void
    {
        $company = $this->userCompany($companyId);

        $this->editingId = $company->id;
        $this->name = $company->name ?? '';
        $this->legal_name = $company->legal_name ?? '';
        $this->tax_id = $company->tax_id ?? '';
        $this->base_currency_code = $company->base_currency_code ?? '';
        $this->is_active = $company->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $this->userCompany($this->editingId)->update($data);
        } else {
            $user = Auth::user();

            $company = Company::query()->create($data);

            $company->users()->attach($user->id, ['role' => 'owner']);

            foreach (Permission::MODULES as $module) {
                Permission::query()->create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'module' => $module,
                    'level' => 'full',
                ]);
            }

            // Já entra direto na empresa recém-criada — senão ela fica
            // criada mas "invisível" até o usuário trocar manualmente
            // no seletor do menu.
            $user->update(['current_company_id' => $company->id]);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Busca a empresa garantindo que o usuário autenticado realmente
     * participa dela — não confia em qualquer id vindo do front-end.
     */
    private function userCompany(int $companyId): Company
    {
        return Auth::user()->companies()->where('companies.id', $companyId)->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'legal_name', 'tax_id', 'base_currency_code', 'editingId']);
        $this->is_active = true;
    }

    public function render()
    {
        $companies = Auth::user()->companies()->orderBy('name')->get();

        return view('livewire.admin.companies', [
            'companies' => $companies,
            'currencies' => Currency::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }
}
