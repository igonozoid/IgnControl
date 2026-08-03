<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo — {{ $entry->description ?: 'Lançamento' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #1f2937;
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px;
            font-size: 14px;
        }
        .toolbar { text-align: right; margin-bottom: 16px; }
        .toolbar button {
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
        }
        .toolbar button:hover { background: #4338ca; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 16px; }
        .company { font-weight: 600; font-size: 16px; }
        .company small { display: block; font-weight: 400; color: #6b7280; font-size: 12px; margin-top: 2px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-expense { background: #fee2e2; color: #b42318; }
        .badge-income { background: #dcfce7; color: #15803d; }
        .badge-transfer { background: #e0e7ff; color: #4338ca; }
        .amount { font-size: 26px; font-weight: 700; margin: 4px 0 20px; }
        .amount-expense { color: #b42318; }
        .amount-income { color: #15803d; }
        dl { display: grid; grid-template-columns: 160px 1fr; row-gap: 10px; column-gap: 12px; margin: 0; }
        dt { color: #6b7280; }
        dd { margin: 0; font-weight: 500; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
            .card { border: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="card">
        <div class="header">
            <div class="company">
                {{ $entry->company->name }}
                @if ($entry->company->tax_id)
                    <small>{{ $entry->company->tax_id }}</small>
                @endif
            </div>
            <span class="badge badge-{{ $entry->type }}">
                {{ ['expense' => 'Despesa', 'income' => 'Receita', 'transfer' => 'Transferência'][$entry->type] }}
            </span>
        </div>

        <div class="amount amount-{{ $entry->type === 'income' ? 'income' : 'expense' }}">
            {{ $entry->currency_code }} {{ number_format((float) $entry->amount, 2, ',', '.') }}
        </div>

        <dl>
            <dt>Descrição</dt>
            <dd>{{ $entry->description ?: '—' }}</dd>

            @if ($entry->type === 'transfer')
                <dt>Conta de origem</dt>
                <dd>{{ $entry->financialAccount?->name ?? '—' }}</dd>
                <dt>Conta de destino</dt>
                <dd>{{ $entry->destinationAccount?->name ?? '—' }}</dd>
            @else
                <dt>Conta</dt>
                <dd>{{ $entry->financialAccount?->name ?? '—' }}</dd>
                <dt>Contato</dt>
                <dd>{{ $entry->contact?->name ?? '—' }}</dd>
                <dt>Categoria</dt>
                <dd>{{ $entry->category?->name ?? '—' }}</dd>
                <dt>Centro de custo</dt>
                <dd>{{ $entry->costCenter?->name ?? '—' }}</dd>
            @endif

            <dt>Vencimento</dt>
            <dd>{{ $entry->due_date->format('d/m/Y') }}</dd>

            <dt>Situação</dt>
            <dd>
                {{ match (true) {
                    $entry->status === 'paid' => 'Pago em ' . $entry->paid_date?->format('d/m/Y'),
                    $entry->status === 'canceled' => 'Cancelado',
                    $entry->due_date->isPast() => 'Atrasado',
                    default => 'Em aberto',
                } }}
            </dd>
        </dl>

        <div class="footer">
            Recibo gerado em {{ now()->format('d/m/Y H:i') }} — lançamento #{{ $entry->id }}
        </div>
    </div>
</body>
</html>
