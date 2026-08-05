<?php

namespace App\Livewire\Hr;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * RH (Administrativo > RH no legado). Lista todo Contact com
 * is_employee = true — funcionário não é uma entidade separada, é um
 * papel do contato (igual já funcionava no legado). "Empregador" (Marco,
 * Vera etc.) não é um campo aqui: cada um é uma empresa (company)
 * separada no sistema, então o isolamento já é automático.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $staffCategory = '';

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('hr', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('hr', 'full');
    }

    public function render()
    {
        $employees = Contact::query()
            ->where('is_employee', true)
            ->with(['employeeProfile', 'benefits' => fn ($q) => $q->where('active', true), 'salaryEntries' => fn ($q) => $q->latest('effective_date')->limit(1)])
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->staffCategory !== '', fn ($q) => $q->whereHas('employeeProfile', fn ($p) => $p->where('staff_category', $this->staffCategory)))
            ->when($this->status !== '', function ($q) {
                // Sem perfil de RH ainda cadastrado conta como "ativo" por
                // padrão (mesma regra usada no resumo mais abaixo) — por
                // isso precisa entrar no OR aqui dentro, sem vazar pra
                // fora do grupo e quebrar o filtro de is_employee.
                $q->where(function ($group) {
                    $group->whereHas('employeeProfile', fn ($p) => $p->where('status', $this->status));
                    if ($this->status === 'active') {
                        $group->orWhereDoesntHave('employeeProfile');
                    }
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (Contact $contact) {
                $currentSalary = $contact->salaryEntries->first()?->net_salary
                    ?? $contact->salaryEntries->first()?->nominal_salary
                    ?? $contact->employeeProfile?->initial_salary
                    ?? 0;

                $benefitsTotal = $contact->benefits->sum('monthly_value');

                return (object) [
                    'contact' => $contact,
                    'currentSalary' => (float) $currentSalary,
                    'benefitsTotal' => (float) $benefitsTotal,
                    'total' => (float) $currentSalary + (float) $benefitsTotal,
                ];
            });

        $summary = [
            'count' => $employees->count(),
            'active' => $employees->filter(fn ($e) => ($e->contact->employeeProfile?->status ?? 'active') === 'active')->count(),
            'salary_total' => $employees->sum('currentSalary'),
            'benefits_total' => $employees->sum('benefitsTotal'),
            'payroll_total' => $employees->sum('total'),
        ];

        return view('livewire.hr.index', [
            'employees' => $employees,
            'summary' => $summary,
        ]);
    }
}
