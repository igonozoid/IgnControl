<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Versão imprimível da tela de Auditoria, com os mesmos filtros da
 * listagem (data, usuário, ação, tipo de registro) vindos por querystring.
 */
class AuditLogPrintController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless(Auth::user()->hasModuleAccess('admin', 'full'), 403);

        $companyId = Auth::user()->current_company_id;

        $filters = [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'user_id' => (string) $request->query('user_id', ''),
            'action' => (string) $request->query('action', ''),
            'model' => (string) $request->query('model', ''),
        ];

        $logs = AuditLog::query()
            ->where('company_id', $companyId)
            ->when($filters['date_from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['user_id'] !== '', fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['model'] !== '', fn ($q) => $q->where('auditable_type', $filters['model']))
            ->with(['user', 'company'])
            ->latest('created_at')
            ->get();

        return view('print.audit-log', [
            'logs' => $logs,
            'filters' => $filters,
            'company' => $logs->first()?->company ?? Auth::user()->currentCompany ?? null,
        ]);
    }
}
