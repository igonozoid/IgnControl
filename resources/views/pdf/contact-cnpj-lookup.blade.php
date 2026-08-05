<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.subtitle { color: #6b7280; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        td.label { width: 220px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Consulta de CNPJ — Busca Básica</h1>
    <p class="subtitle">Gerado em {{ now()->format('d/m/Y H:i') }} — fonte: BrasilAPI (dados públicos da Receita Federal)</p>

    <table>
        <tr><td class="label">CNPJ</td><td>{{ $dados['cnpj'] ?? '—' }}</td></tr>
        <tr><td class="label">Razão social</td><td>{{ $dados['razao_social'] ?? '—' }}</td></tr>
        <tr><td class="label">Nome fantasia</td><td>{{ $dados['nome_fantasia'] ?? '—' }}</td></tr>
        <tr><td class="label">Situação cadastral</td><td>{{ $dados['descricao_situacao_cadastral'] ?? '—' }}</td></tr>
        <tr><td class="label">Data de abertura</td><td>{{ $dados['data_inicio_atividade'] ?? '—' }}</td></tr>
        <tr><td class="label">Atividade principal</td><td>{{ $dados['cnae_fiscal_descricao'] ?? '—' }}</td></tr>
        <tr><td class="label">Endereço</td><td>{{ trim(($dados['logradouro'] ?? '').', '.($dados['numero'] ?? ''), ', ') }} {{ $dados['complemento'] ?? '' }}</td></tr>
        <tr><td class="label">Bairro</td><td>{{ $dados['bairro'] ?? '—' }}</td></tr>
        <tr><td class="label">Cidade/UF</td><td>{{ $dados['municipio'] ?? '—' }} / {{ $dados['uf'] ?? '—' }}</td></tr>
        <tr><td class="label">CEP</td><td>{{ $dados['cep'] ?? '—' }}</td></tr>
        <tr><td class="label">Telefone</td><td>{{ $dados['ddd_telefone_1'] ?? '—' }}</td></tr>
        <tr><td class="label">E-mail</td><td>{{ $dados['email'] ?? '—' }}</td></tr>
        <tr><td class="label">Capital social</td><td>{{ isset($dados['capital_social']) ? 'R$ '.number_format((float) $dados['capital_social'], 2, ',', '.') : '—' }}</td></tr>
        <tr><td class="label">Porte</td><td>{{ $dados['porte'] ?? '—' }}</td></tr>
    </table>
</body>
</html>
