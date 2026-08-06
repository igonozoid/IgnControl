<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Tela de administração: dados cadastrais das empresas que o usuário
 * participa (razão social, CNPJ, endereço, contato, moeda base,
 * ativa/inativa, módulos verticais ativos) e criação de novas empresas.
 *
 * `locked_through` (fechamento de período) não entra aqui de propósito
 * — já tem tela própria em Admin\PeriodLock, não faz sentido duplicar.
 */
#[Layout('layouts.app')]
class Companies extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|in:PF,PJ')]
    public string $person_type = 'PJ';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $legal_name = '';

    #[Validate('nullable|string|max:32')]
    public string $tax_id = '';

    #[Validate('nullable|string|max:32')]
    public string $document_secondary = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $website = '';

    #[Validate('nullable|string|max:255')]
    public string $address_line1 = '';

    #[Validate('nullable|string|max:255')]
    public string $address_line2 = '';

    #[Validate('nullable|string|max:255')]
    public string $district = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    #[Validate('nullable|string|max:2')]
    public string $state = '';

    #[Validate('nullable|string|max:16')]
    public string $postal_code = '';

    #[Validate('nullable|string|max:255')]
    public string $country = '';

    #[Validate('required|string|size:3|exists:currencies,code')]
    public string $base_currency_code = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    /** @var array<string, bool> */
    public array $optionalModules = [];

    // Logo: mesmo padrão da foto do contato — upload temporário do
    // Livewire ($logo), caminho já salvo só pra prévia
    // ($existingLogoPath), e flag de remoção ($removeLogo).
    public $logo = null;

    public ?string $existingLogoPath = null;

    public bool $removeLogo = false;

    // Busca Básica (CNPJ): mesmo fluxo usado em Contacts\Form.
    public bool $searchingCnpj = false;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
    }

    public function getIsCnpjDocumentProperty(): bool
    {
        return $this->person_type === 'PJ';
    }

    public function getLogoPreviewUrlProperty(): ?string
    {
        if ($this->logo) {
            return $this->logo->temporaryUrl();
        }

        if ($this->existingLogoPath && ! $this->removeLogo && $this->editingId) {
            return route('admin.companies.logo', $this->editingId);
        }

        return null;
    }

    public function removeLogoNow(): void
    {
        $this->logo = null;
        $this->removeLogo = true;
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
        $this->person_type = $company->person_type ?: 'PJ';
        $this->name = $company->name ?? '';
        $this->legal_name = $company->legal_name ?? '';
        $this->tax_id = $company->tax_id ?? '';
        $this->document_secondary = $company->document_secondary ?? '';
        $this->email = $company->email ?? '';
        $this->phone = $company->phone ?? '';
        $this->website = $company->website ?? '';
        $this->address_line1 = $company->address_line1 ?? '';
        $this->address_line2 = $company->address_line2 ?? '';
        $this->district = $company->district ?? '';
        $this->city = $company->city ?? '';
        $this->state = $company->state ?? '';
        $this->postal_code = $company->postal_code ?? '';
        $this->country = $company->country ?? '';
        $this->base_currency_code = $company->base_currency_code ?? '';
        $this->is_active = $company->is_active;
        $this->existingLogoPath = $company->logo_path;
        $this->removeLogo = false;
        $this->logo = null;
        $this->optionalModules = collect(Company::OPTIONAL_MODULES)
            ->mapWithKeys(fn (string $module) => [$module => in_array($module, $company->enabled_modules ?? [], true)])
            ->all();
        $this->showForm = true;
    }

    /**
     * Busca Básica: consulta o CNPJ na BrasilAPI (fonte pública gratuita,
     * dados da Receita Federal) e preenche os campos automaticamente.
     * Mesma fonte/fluxo usado em Contacts\Form::buscarCnpj().
     */
    public function buscarCnpj(): void
    {
        $cnpj = preg_replace('/\D/', '', $this->tax_id);

        if (strlen($cnpj) !== 14) {
            $this->addError('tax_id', 'Informe um CNPJ válido (14 dígitos) para usar a Busca Básica.');

            return;
        }

        $this->searchingCnpj = true;

        $response = Http::timeout(15)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

        $this->searchingCnpj = false;

        if ($response->failed()) {
            $this->addError('tax_id', 'Não foi possível consultar o CNPJ agora. Tente novamente em instantes.');

            return;
        }

        $dados = $response->json();

        $this->legal_name = $dados['razao_social'] ?? $this->legal_name;
        $this->name = $this->name ?: ($dados['nome_fantasia'] ?: $dados['razao_social'] ?? $this->name);
        $this->address_line1 = trim(($dados['logradouro'] ?? '').($dados['numero'] ? ', '.$dados['numero'] : ''));
        $this->address_line2 = $dados['complemento'] ?? $this->address_line2;
        $this->district = $dados['bairro'] ?? $this->district;
        $this->city = $dados['municipio'] ?? $this->city;
        $this->state = $dados['uf'] ?? $this->state;
        $this->postal_code = $dados['cep'] ?? $this->postal_code;
        $this->country = 'Brasil';

        if ($this->phone === '' && ! empty($dados['ddd_telefone_1'])) {
            $this->phone = $dados['ddd_telefone_1'];
        }
        if ($this->email === '' && ! empty($dados['email'])) {
            $this->email = $dados['email'];
        }
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['enabled_modules'] = collect($this->optionalModules)->filter()->keys()->values()->all();

        if ($this->editingId) {
            $company = $this->userCompany($this->editingId);
            $this->syncLogo($company);
            $company->update($data);
        } else {
            $user = Auth::user();

            $company = Company::query()->create($data);
            $this->syncLogo($company);

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

    private function syncLogo(Company $company): void
    {
        if ($this->logo) {
            if ($company->logo_path) {
                Storage::disk('local')->delete($company->logo_path);
            }
            $path = $this->logo->store("companies/{$company->id}/logo", 'local');
            $company->update(['logo_path' => $path]);
        } elseif ($this->removeLogo && $company->logo_path) {
            Storage::disk('local')->delete($company->logo_path);
            $company->update(['logo_path' => null]);
        }
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
        $this->reset([
            'name', 'legal_name', 'tax_id', 'document_secondary', 'email', 'phone', 'website',
            'address_line1', 'address_line2', 'district', 'city', 'state', 'postal_code', 'country',
            'base_currency_code', 'editingId', 'logo', 'existingLogoPath', 'removeLogo',
        ]);
        $this->person_type = 'PJ';
        $this->is_active = true;
        // Empresa nova nasce sem nenhum módulo vertical marcado — quem
        // cria escolhe o que se aplica ao negócio.
        $this->optionalModules = collect(Company::OPTIONAL_MODULES)->mapWithKeys(fn (string $module) => [$module => false])->all();
    }

    public function render()
    {
        $companies = Auth::user()->companies()->orderBy('name')->get();

        return view('livewire.admin.companies', [
            'companies' => $companies,
            'currencies' => Currency::query()->where('is_active', true)->orderBy('code')->get(),
            'moduleLabels' => [
                'hr' => 'RH',
                'inventory' => 'Estoque',
                'sales' => 'Vendas',
                'rural' => 'Rural',
                'cost_centers' => 'Centros de Custo',
            ],
        ]);
    }
}
