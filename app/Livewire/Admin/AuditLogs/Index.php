<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Auditoria (Configurações > Auditoria no legado). Lista tudo que o
 * Auditable e os registros manuais (ex.: visualizar/copiar senha do
 * cofre) gravaram em audit_logs, com filtros, e um link pra versão
 * imprimível com os mesmos filtros aplicados.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $dateFrom = '';
    #[Url]
    public string $dateTo = '';
    #[Url]
    public string $userId = '';
    #[Url]
    public string $action = '';
    #[Url]
    public string $model = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingModel(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo', 'userId', 'action', 'model']);
        $this->resetPage();
    }

    public function getPrintUrlProperty(): string
    {
        return route('admin.audit.print', array_filter([
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'user_id' => $this->userId,
            'action' => $this->action,
            'model' => $this->model,
        ]));
    }

    public function render()
    {
        $companyId = Auth::user()->current_company_id;

        $logs = AuditLog::query()
            ->where('company_id', $companyId)
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->action !== '', fn ($q) => $q->where('action', $this->action))
            ->when($this->model !== '', fn ($q) => $q->where('auditable_type', $this->model))
            ->with('user')
            ->latest('created_at')
            ->paginate(30);

        $users = User::query()
            ->whereIn('id', AuditLog::query()->where('company_id', $companyId)->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'models' => AuditLog::auditableModels(),
        ]);
    }
}
