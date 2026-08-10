<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo — {{ $reference ?: 'Lançamento' }} #{{ $entry->id }}</title>
    <style>
        * { box-sizing: border-box; }

        @page { size: A4; margin: 10mm; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #111827;
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
            border: 1px solid #9ca3af;
            padding: 8mm;
            margin-bottom: 6mm;
        }

        .cut-line {
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
            letter-spacing: 2px;
            margin: -3mm 0 6mm;
        }

        .via-tag {
            display: block;
            text-align: right;
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 2mm;
        }

        /* Cabeçalho — mesmo padrão usado nos demais relatórios do
           sistema: logo à esquerda, dados da empresa centralizados no
           espaço restante, separador, título e subtítulo. */
        .letterhead { display: flex; align-items: flex-start; gap: 4mm; }
        .letterhead .logo { width: 26mm; flex-shrink: 0; }
        .letterhead .logo img { max-width: 100%; max-height: 16mm; object-fit: contain; }
        .letterhead .entity { flex: 1; text-align: center; }
        .letterhead .entity .name { font-weight: 700; font-size: 14px; }
        .letterhead .entity .line { font-size: 9px; color: #374151; margin-top: 1mm; }

        .separator { border: none; border-top: 1px solid #111827; margin: 3mm 0; }

        .title { text-align: center; font-weight: 700; font-size: 15px; letter-spacing: 1px; }
        .subtitle { text-align: center; font-size: 10px; color: #374151; margin: 1mm 0 5mm; }

        .field { margin: 0 0 6mm; font-size: 12px; }
        .field .label { color: #374151; }

        .value-date-row { display: flex; gap: 16mm; margin: 0 0 6mm; font-size: 12px; }

        .importance {
            font-style: italic;
            font-size: 10px;
            margin: 0 0 8mm;
        }

        .signature { margin-top: 10mm; }
        .signature .line { border-top: 1px solid #111827; width: 100%; }
        .signature .name { text-align: center; font-size: 11px; font-weight: 600; margin-top: 1.5mm; }
        .signature .doc { text-align: center; font-size: 9px; color: #6b7280; margin-top: 0.5mm; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .page { max-width: none; }
            .via { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $signatureName = $isExpense ? $partyName : $entityName;
        $company = $entry->company;

        $addressLine = trim(collect([
            $company->address_line1,
            $company->address_line2,
            $company->district,
            $company->city,
            $company->state,
            $company->postal_code ? 'CEP: '.$company->postal_code : null,
        ])->filter()->implode(' '));

        $contactsLine = trim(collect([
            $company->phone ? 'Tel: '.$company->phone : null,
            $company->email ? 'e-mail: '.$company->email : null,
            $company->website ? 'site: '.$company->website : null,
        ])->filter()->implode('  '));

        $docsLine = trim(collect([$company->tax_id, $company->document_secondary])->filter()->implode('  '));

        $currencyPrefix = $entry->currency_code === 'BRL' ? 'R$' : $entry->currency_code;
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

                <div class="letterhead">
                    @if ($company->logo_path)
                        <div class="logo"><img src="{{ route('admin.companies.logo', $company) }}" alt="Logo"></div>
                    @endif
                    <div class="entity">
                        <div class="name">{{ $company->name }}</div>
                        @if ($addressLine)
                            <div class="line">{{ $addressLine }}</div>
                        @endif
                        @if ($contactsLine)
                            <div class="line">{{ $contactsLine }}</div>
                        @endif
                        @if ($docsLine)
                            <div class="line">{{ $docsLine }}</div>
                        @endif
                    </div>
                </div>

                <hr class="separator">

                <div class="title">RECIBO</div>
                <div class="subtitle">Data: {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} | Documento: {{ $document ?: '-' }}</div>

                <p class="field"><span class="label">{{ $partyLabel }}:</span> {{ $partyName ?: '—' }}</p>
                <p class="field"><span class="label">{{ $entityLabel }}:</span> {{ $entityName ?: '—' }}</p>

                <div class="value-date-row">
                    <span><strong>Valor:</strong> {{ $currencyPrefix }} {{ number_format($amount, 2, ',', '.') }}</span>
                    <span>Data: {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</span>
                </div>

                <p class="field"><span class="label">Documento:</span> {{ $document ?: '-' }}</p>

                <p class="importance"><span class="label">Importância:</span> {{ $amountWords ?: '—' }}</p>

                <p class="field"><span class="label">Referente a:</span> {{ $reference ?: '—' }}</p>
                <p class="field"><span class="label">Observação:</span> {{ $notes ?: '-' }}</p>

                <div class="signature">
                    <div class="line"></div>
                    <div class="name">{{ strtoupper($signatureName ?: '—') }}</div>
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
