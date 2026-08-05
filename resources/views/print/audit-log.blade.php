<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Auditoria — {{ $company?->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #1f2937;
            margin: 30px auto;
            padding: 0 20px;
            font-size: 12px;
            max-width: 1000px;
        }
        .toolbar { text-align: right; margin-bottom: 16px; }
        .toolbar button {
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
        }
        .toolbar button:hover { background: #15803d; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 12px; }
        .company { font-weight: 600; font-size: 15px; }
        .title { font-size: 13px; color: #6b7280; margin-top: 2px; }
        .filters { font-size: 11px; color: #6b7280; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { font-size: 10px; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
        td { font-size: 11px; }
        .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="header">
        <div>
            <div class="company">{{ $company?->name }}</div>
            <div class="title">Relatório de Auditoria</div>
        </div>
        <div style="text-align: right; font-size: 11px; color: #6b7280;">
            Gerado em {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="filters">
        Filtros:
        Período: {{ $filters['date_from'] ?: 'início' }} até {{ $filters['date_to'] ?: 'hoje' }}
        @if ($filters['user_id']) &middot; Usuário: {{ \App\Models\User::find($filters['user_id'])?->name ?? $filters['user_id'] }} @endif
        @if ($filters['action']) &middot; Ação: {{ \App\Models\AuditLog::actionLabel($filters['action']) }} @endif
        @if ($filters['model']) &middot; Tipo: {{ \App\Models\AuditLog::modelLabel($filters['model']) }} @endif
        &middot; {{ $logs->count() }} registro(s)
    </div>

    <table>
        <thead>
            <tr>
                <th>Data/hora</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Registro</th>
                <th>Detalhe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td>{{ \App\Models\AuditLog::actionLabel($log->action) }}</td>
                    <td>{{ \App\Models\AuditLog::modelLabel($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                    <td>
                        @if ($log->action === 'updated' && $log->new_values)
                            {{ collect($log->new_values)->keys()->implode(', ') }}
                        @elseif (in_array($log->action, ['viewed', 'copied']))
                            valor não registrado (apenas o acesso)
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9ca3af;">Nenhum registro encontrado com esses filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento gerado pelo IgnControl — uso interno.
    </div>
</body>
</html>
