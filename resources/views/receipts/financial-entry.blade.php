<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo — {{ $entry->description ?: 'Lançamento' }} #{{ $entry->id }}</title>
    <style>
        * { box-sizing: border-box; }

        @page { size: A4; margin: 10mm; }

        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            background: #f3f4f6;
        }

        .toolbar { max-width: 190mm; margin: 0 auto 16px; text-align: right; }
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

        .page { max-width: 190mm; margin: 0 auto; background: #fff; }

        /* Meia folha A4 por via — duas vias empilhadas na mesma página,
           igual ao padrão do recibo do sistema legado. */
        .via {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        .via + .via { border-top-style: dashed; }

        .cut-line {
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
            letter-spacing: 2px;
            margin: -6px 0 14px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-company { display: flex; align-items: center; gap: 10px; }
        .header-company img { height: 40px; width: 40px; object-fit: contain; }
        .header-company .name { font-weight: 700; font-size: 15px; }
        .header-company .meta { font-size: 11px; color: #6b7280; line-height: 1.4; }
        .header-doc { text-align: right; font-size: 11px; color: #6b7280; }
        .header-doc .title { font-weight: 700; font-size: 14px; color: #1f2937; }

        .via-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: #4338ca;
            background: #e0e7ff;
            border-radius: 999px;
            padding: 1px 8px;
            margin-bottom: 8px;
        }

        .lead {
            font-size: 13px;
            line-height: 1.7;
            margin: 0 0 10px;
        }
        .lead strong { font-weight: 700; }

        .amount-box {
            display: inline-block;
            border: 1px solid #1f2937;
            border-radius: 6px;
            padding: 4px 12px;
            font-weight: 700;
            font-size: 15px;
        }

        .extenso {
            font-style: italic;
            margin: 10px 0;
        }

        dl.fields {
            display: grid;
            grid-template-columns: 110px 1fr;
            row-gap: 4px;
            column-gap: 10px;
            margin: 10px 0 0;
            font-size: 12px;
        }
        dl.fields dt { color: #6b7280; }
        dl.fields dd { margin: 0; }

        .signature {
            margin-top: 22px;
            padding-top: 6px;
            border-top: 1px solid #9ca3af;
            width: 70%;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 10px;
            color: #9ca3af;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .page { max-width: none; }
            .via { border-color: #6b7280; border-radius: 0; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $company = $entry->company;
        $isTransfer = $entry->type === 'transfer';
        $isIncome = $entry->type === 'income';

        $counterpartLabel = match (true) {
            $isTransfer => 'Transferência',
            $isIncome => 'Recebido de',
            default => 'Pagamento para',
        };

        $counterpartName = match (true) {
            $isTransfer => trim(($entry->financialAccount?->name ?? '—').' → '.($entry->destinationAccount?->name ?? '—')),
            default => $entry->contact?->name ?? '—',
        };

        // Data de referência do recibo: quando já baixado, a data em que
        // efetivamente pagou/recebeu; em aberto, o vencimento — mesma
        // regra do relatório analítico legado (paid/received usa a data
        // de baixa, senão usa o vencimento).
        $referenceDate = $entry->paid_date ?? $entry->due_date;

        $valorExtenso = \App\Support\ValorPorExtenso::porExtenso((float) $entry->amount, $entry->currency_code);

        $situacao = match (true) {
            $entry->status === 'paid' => 'Pago em '.$entry->paid_date?->format('d/m/Y'),
            $entry->status === 'canceled' => 'Cancelado',
            $entry->due_date->isPast() => 'Atrasado',
            default => 'Em aberto',
        };
    @endphp

    <div class="toolbar">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="page">
        @for ($via = 1; $via <= 2; $via++)
            <div class="via">
                <span class="via-tag">{{ $via }}ª via</span>

                <div class="header">
                    <div class="header-company">
                        @if ($company->logo_path)
                            <img src="{{ route('admin.companies.logo', $company) }}" alt="Logo">
                        @endif
                        <div>
                            <div class="name">{{ $company->name }}</div>
                            <div class="meta">
                                @if ($company->tax_id) {{ $company->tax_id }} @endif
                                @if ($company->address_line1)
                                    <br>{{ $company->address_line1 }}{{ $company->city ? ' — '.$company->city.'/'.$company->state : '' }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="header-doc">
                        <div class="title">RECIBO</div>
                        Nº {{ str_pad($entry->id, 6, '0', STR_PAD_LEFT) }}<br>
                        Emitido em {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>

                <p class="lead">
                    <strong>{{ $counterpartLabel }}:</strong> {{ $counterpartName }}
                </p>

                <p class="lead">
                    Valor: <span class="amount-box">{{ $entry->currency_code }} {{ number_format((float) $entry->amount, 2, ',', '.') }}</span>
                </p>

                <p class="extenso">
                    ({{ ucfirst($valorExtenso) }})
                </p>

                <dl class="fields">
                    <dt>Data</dt>
                    <dd>{{ $referenceDate->format('d/m/Y') }}</dd>

                    <dt>Documento</dt>
                    <dd>{{ $entry->document_number ?: '—' }}</dd>

                    <dt>Referente a</dt>
                    <dd>{{ $entry->description ?: '—' }}</dd>

                    @if ($entry->notes)
                        <dt>Observação</dt>
                        <dd>{{ $entry->notes }}</dd>
                    @endif

                    @unless ($isTransfer)
                        <dt>Categoria</dt>
                        <dd>{{ $entry->category?->name ?? '—' }}</dd>

                        <dt>Centro de custo</dt>
                        <dd>{{ $entry->costCenter?->name ?? '—' }}</dd>
                    @endunless

                    <dt>Situação</dt>
                    <dd>{{ $situacao }}</dd>
                </dl>

                <div class="signature">Assinatura</div>

                <div class="footer-note">
                    Documento gerado pelo sistema — lançamento interno #{{ $entry->id }}.
                </div>
            </div>

            @if ($via === 1)
                <div class="cut-line">✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</div>
            @endif
        @endfor
    </div>
</body>
</html>
