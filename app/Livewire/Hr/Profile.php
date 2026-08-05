<?php

namespace App\Livewire\Hr;

use App\Models\Contact;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSalaryEntry;
use App\Models\EmployeeThirteenthSalary;
use App\Models\EmployeeVacation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ficha de RH de um funcionário (Contact com is_employee = true). Uma
 * única tela com abas: dados do perfil, evolução salarial, férias, 13º,
 * benefícios recorrentes e custo/encargos (calculado, não é uma tabela —
 * a planilha "Custo p/ Funcionário" do usuário é quase toda derivada do
 * salário + alíquota INSS, então calculamos em vez de pedir pra digitar
 * de novo).
 */
#[Layout('layouts.app')]
class Profile extends Component
{
    // Percentuais fixos de encargos de empregado doméstico/CLT usados no
    // cálculo de custo — os mesmos da planilha "Custo p Funcionário"
    // (FGTS 8%, 13º = 1/12, 1/3 de férias = 1/36).
    private const FGTS_RATE = 0.08;

    private const THIRTEENTH_PROVISION_RATE = 1 / 12;

    private const VACATION_THIRD_PROVISION_RATE = 1 / 36;

    public Contact $contact;

    public string $tab = 'profile';

    // --- Perfil ---
    public string $job_title = '';

    public string $staff_category = 'domestic_rural';

    public string $employment_type = '';

    public string $union_name = '';

    public string $workplace_location = '';

    public string $city = '';

    public string $state = '';

    public string $inss_rate = '';

    public string $monthly_hours = '';

    public string $dissidio_reference = '';

    public string $initial_salary = '';

    public string $admission_date = '';

    public string $termination_date = '';

    public string $status = 'active';

    public string $profile_notes = '';

    // --- Formulário genérico pros sub-recursos (histórico salarial,
    // férias, 13º, benefícios) ---
    public bool $showForm = false;

    public ?string $formType = null;

    public ?int $editingId = null;

    public array $form = [];

    public function mount(Contact $contact): void
    {
        abort_unless(Auth::user()->hasModuleAccess('hr', 'read'), 403);
        abort_unless($contact->is_employee, 404);

        $this->contact = $contact;
        $this->loadProfile();
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('hr', 'full');
    }

    private function loadProfile(): void
    {
        $profile = $this->contact->employeeProfile;

        $this->job_title = (string) $profile?->job_title;
        $this->staff_category = $profile?->staff_category ?? 'domestic_rural';
        $this->employment_type = (string) $profile?->employment_type;
        $this->union_name = (string) $profile?->union_name;
        $this->workplace_location = (string) $profile?->workplace_location;
        $this->city = (string) $profile?->city;
        $this->state = (string) $profile?->state;
        $this->inss_rate = $profile?->inss_rate !== null ? (string) $profile->inss_rate : '';
        $this->monthly_hours = $profile?->monthly_hours !== null ? (string) $profile->monthly_hours : '';
        $this->dissidio_reference = (string) $profile?->dissidio_reference;
        $this->initial_salary = $profile?->initial_salary !== null ? (string) $profile->initial_salary : '';
        $this->admission_date = $profile?->admission_date?->format('Y-m-d') ?? '';
        $this->termination_date = $profile?->termination_date?->format('Y-m-d') ?? '';
        $this->status = $profile?->status ?? 'active';
        $this->profile_notes = (string) $profile?->notes;
    }

    public function saveProfile(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'job_title' => 'nullable|string|max:255',
            'staff_category' => 'required|in:domestic_rural,office',
            'employment_type' => 'nullable|string|max:255',
            'union_name' => 'nullable|string|max:255',
            'workplace_location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'inss_rate' => 'nullable|numeric|min:0|max:1',
            'monthly_hours' => 'nullable|numeric|min:0',
            'dissidio_reference' => 'nullable|string|max:255',
            'initial_salary' => 'nullable|numeric|min:0',
            'admission_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'status' => 'required|in:active,terminated',
            'profile_notes' => 'nullable|string',
        ]);

        EmployeeProfile::query()->updateOrCreate(
            ['contact_id' => $this->contact->id],
            [
                'company_id' => $this->contact->company_id,
                'job_title' => $data['job_title'] ?: null,
                'staff_category' => $data['staff_category'],
                'employment_type' => $data['employment_type'] ?: null,
                'union_name' => $data['union_name'] ?: null,
                'workplace_location' => $data['workplace_location'] ?: null,
                'city' => $data['city'] ?: null,
                'state' => $data['state'] ?: null,
                'inss_rate' => $data['inss_rate'] ?: null,
                'monthly_hours' => $data['monthly_hours'] ?: null,
                'dissidio_reference' => $data['dissidio_reference'] ?: null,
                'initial_salary' => $data['initial_salary'] ?: null,
                'admission_date' => $data['admission_date'] ?: null,
                'termination_date' => $data['termination_date'] ?: null,
                'status' => $data['status'],
                'notes' => $data['profile_notes'] ?: null,
            ]
        );

        $this->contact->unsetRelation('employeeProfile');
        $this->dispatch('profile-saved');
    }

    // --- CRUD genérico dos sub-recursos ---

    private function modelFor(string $type): string
    {
        return match ($type) {
            'salary' => EmployeeSalaryEntry::class,
            'vacation' => EmployeeVacation::class,
            'thirteenth' => EmployeeThirteenthSalary::class,
            'benefit' => EmployeeBenefit::class,
            default => throw new \InvalidArgumentException("Tipo inválido: {$type}"),
        };
    }

    private function rulesFor(string $type): array
    {
        return match ($type) {
            'salary' => [
                'effective_date' => 'required|date',
                'nominal_salary' => 'nullable|numeric|min:0',
                'dissidio_percent' => 'nullable|numeric',
                'net_salary' => 'nullable|numeric|min:0',
                'hourly_value' => 'nullable|numeric|min:0',
                'benefits_value' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ],
            'vacation' => [
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:form.period_start',
                'leave_start' => 'nullable|date',
                'leave_end' => 'nullable|date',
                'payment_date' => 'nullable|date',
                'amount_paid' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ],
            'thirteenth' => [
                'year' => 'required|integer|min:2000|max:2100',
                'amount_paid' => 'nullable|numeric|min:0',
                'payment_date' => 'nullable|date',
                'notes' => 'nullable|string',
            ],
            'benefit' => [
                'name' => 'required|string|max:255',
                'monthly_value' => 'required|numeric|min:0',
                'active' => 'boolean',
                'notes' => 'nullable|string',
            ],
            default => [],
        };
    }

    private function emptyFormFor(string $type): array
    {
        return match ($type) {
            'salary' => ['effective_date' => '', 'nominal_salary' => '', 'dissidio_percent' => '', 'net_salary' => '', 'hourly_value' => '', 'benefits_value' => '', 'notes' => ''],
            'vacation' => ['period_start' => '', 'period_end' => '', 'leave_start' => '', 'leave_end' => '', 'payment_date' => '', 'amount_paid' => '', 'notes' => ''],
            'thirteenth' => ['year' => (string) now()->year, 'amount_paid' => '', 'payment_date' => '', 'notes' => ''],
            'benefit' => ['name' => '', 'monthly_value' => '', 'active' => true, 'notes' => ''],
            default => [],
        };
    }

    public function createEntry(string $type): void
    {
        abort_unless($this->canWrite, 403);
        $this->formType = $type;
        $this->editingId = null;
        $this->form = $this->emptyFormFor($type);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function editEntry(string $type, int $id): void
    {
        abort_unless($this->canWrite, 403);
        $model = $this->modelFor($type)::query()->where('contact_id', $this->contact->id)->findOrFail($id);

        $this->formType = $type;
        $this->editingId = $id;
        $this->form = collect($this->emptyFormFor($type))
            ->keys()
            ->mapWithKeys(function ($key) use ($model) {
                $value = $model->{$key};

                return [$key => $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : (string) ($value ?? '')];
            })
            ->all();

        if ($type === 'benefit') {
            $this->form['active'] = (bool) $model->active;
        }

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function saveEntry(): void
    {
        abort_unless($this->canWrite, 403);
        abort_unless($this->formType, 400);

        $rules = collect($this->rulesFor($this->formType))
            ->mapWithKeys(fn ($rule, $field) => ["form.{$field}" => $rule])
            ->all();

        $validated = $this->validate($rules);
        $data = $validated['form'];

        // Campos numéricos/data vazios viram null em vez de string vazia.
        foreach ($data as $key => $value) {
            if ($key !== 'active' && $value === '') {
                $data[$key] = null;
            }
        }

        $modelClass = $this->modelFor($this->formType);

        if ($this->editingId) {
            $modelClass::query()->where('contact_id', $this->contact->id)->findOrFail($this->editingId)->update($data);
        } else {
            $data['company_id'] = $this->contact->company_id;
            $data['contact_id'] = $this->contact->id;
            $modelClass::query()->create($data);
        }

        $this->showForm = false;
        $this->formType = null;
        $this->editingId = null;
        $this->form = [];
    }

    public function deleteEntry(string $type, int $id): void
    {
        abort_unless($this->canWrite, 403);
        $this->modelFor($type)::query()->where('contact_id', $this->contact->id)->findOrFail($id)->delete();
    }

    public function cancelEntry(): void
    {
        $this->showForm = false;
        $this->formType = null;
        $this->editingId = null;
        $this->form = [];
    }

    /** Custo/encargos estimado a partir do salário atual + alíquota INSS do perfil. */
    public function getCostProperty(): array
    {
        $profile = $this->contact->employeeProfile;
        $latestSalary = $this->contact->salaryEntries()->latest('effective_date')->first();

        $baseSalary = (float) ($latestSalary?->net_salary ?? $latestSalary?->nominal_salary ?? $profile?->initial_salary ?? 0);
        $inssRate = (float) ($profile?->inss_rate ?? 0);

        $inss = $baseSalary * $inssRate;
        $fgts = $baseSalary * self::FGTS_RATE;
        $thirteenthProvision = $baseSalary * self::THIRTEENTH_PROVISION_RATE;
        $inssOnThirteenth = $thirteenthProvision * $inssRate;
        $fgtsOnThirteenth = $thirteenthProvision * self::FGTS_RATE;
        $vacationThirdProvision = $baseSalary * self::VACATION_THIRD_PROVISION_RATE;
        $inssOnVacation = $vacationThirdProvision * $inssRate;
        $fgtsOnVacation = $vacationThirdProvision * self::FGTS_RATE;

        $totalCharges = $inss + $fgts + $thirteenthProvision + $inssOnThirteenth + $fgtsOnThirteenth
            + $vacationThirdProvision + $inssOnVacation + $fgtsOnVacation;

        return [
            'base_salary' => $baseSalary,
            'inss_rate' => $inssRate,
            'inss' => $inss,
            'fgts' => $fgts,
            'thirteenth_provision' => $thirteenthProvision,
            'inss_on_thirteenth' => $inssOnThirteenth,
            'fgts_on_thirteenth' => $fgtsOnThirteenth,
            'vacation_third_provision' => $vacationThirdProvision,
            'inss_on_vacation' => $inssOnVacation,
            'fgts_on_vacation' => $fgtsOnVacation,
            'total_charges' => $totalCharges,
            'total_cost' => $baseSalary + $totalCharges,
        ];
    }

    public function render()
    {
        return view('livewire.hr.profile', [
            'salaryEntries' => $this->contact->salaryEntries()->orderByDesc('effective_date')->get(),
            'vacations' => $this->contact->vacations()->orderByDesc('period_start')->get(),
            'thirteenthSalaries' => $this->contact->thirteenthSalaries()->orderByDesc('year')->get(),
            'benefitEntries' => $this->contact->benefits()->orderBy('name')->get(),
        ]);
    }
}
