<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo — {{ $reference ?: 'Lançamento' }} #{{ $entry->id }}</title>
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

        /* Meia folha A4 por via — até duas vias empilhadas na mesma
           página, igual ao padrão do recibo do sistema legado. */
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

        .field { margin: 0 0 9px; font-size: 13px; }
        .field .label { color: #6b7280; }

        .value-date-row { display: flex; gap: 24px; margin: 0 0 9px; }

        .extenso {
            font-style: italic;
            font-size: 12px;
            margin: 0 0 12px;
        }

        .signature {
            margin-top: 26px;
            text-align: center;
        }
        .signature .line {
            width: 75%;
            margin: 0 auto;
            border-top: 1px solid #6b7280;
            padding-top: 4px;
            font-size: 12px;
        }
        .signature .doc {
            font-size: 10px;
            color: #6b7280;
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
        $signatureName = $isExpense ? $partyName : $entityName;
    @endphp

    <div class="toolbar">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="page">
        @for ($via = 1; $via <= $copies; $via++)
            <div class="via">
                @if ($copies > 1)
                    <span class="via-tag">{{ $via }}ª via</span>
                @endif

                <div class="header">
                    <div class="header-company">
                        @if ($entry->company->logo_path)
                            <img src="{{ route('admin.companies.logo', $entry->company) }}" alt="Logo">
                        @endif
                        <div>
                            <div class="name">{{ $entry->company->name }}</div>
                            <div class="meta">
                                @if ($entry->company->tax_id) {{ $entry->company->tax_id }} @endif
                                @if ($entry->company->address_line1)
                                    <br>{{ $entry->company->address_line1 }}{{ $entry->company->city ? ' — '.$entry->company->city.'/'.$entry->company->state : '' }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="header-doc">
                        <div class="title">RECIBO</div>
                        Nº {{ str_pad($entry->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                </div>

                <p class="field"><span class="label">{{ $partyLabel }}:</span> {{ $partyName ?: '—' }}</p>
                <p class="field"><span class="label">{{ $entityLabel }}:</span> {{ $entityName ?: '—' }}</p>

                <div class="value-date-row">
                    <p class="field"><span class="label">Valor:</span> <strong>{{ $entry->currency_code }} {{ number_format($amount, 2, ',', '.') }}</strong></p>
                    <p class="field"><span class="label">Data:</span> {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</p>
                </div>

                <p class="field"><span class="label">Documento:</span> {{ $document ?: '—' }}</p>

                <p class="extenso">({{ $amountWords ?: '—' }})</p>

                <p class="field"><span class="label">Referente a:</span> {{ $reference ?: '—' }}</p>

                @if ($notes)
                    <p class="field"><span class="label">Observação:</span> {{ $notes }}</p>
                @endif

                <div class="signature">
                    <div class="line">{{ strtoupper($signatureName ?: '—') }}</div>
                    @if ($signatureDocument)
                        <div class="doc">CPF/CNPJ: {{ $signatureDocument }}</div>
                    @endif
                </div>
            </div>

            @if ($via < $copies)
                <div class="cut-line">✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</div>
            @endif
        @endfor
    </div>
</body>
</html>
