# Decisões de arquitetura — explicadas em português simples

## Multiempresa (multi-tenant)

Toda tabela de negócio (contas financeiras, categorias, contatos,
lançamentos, etc.) tem uma coluna `company_id`. Um usuário pode ter acesso
a mais de uma empresa (tabela `company_user`, com o papel dele em cada
uma). Quando o usuário está logado, ele tem uma "empresa ativa" (guardada
na sessão); todo dado que ele vê ou cria é automaticamente filtrado/
carimbado com essa empresa, via um mecanismo do Laravel chamado *global
scope* (implementado no trait `BelongsToCompany`). Na prática: você nunca
vai ver dado de uma empresa vazando para outra, porque o filtro está no
nível do model, não espalhado manualmente em cada tela.

## Permissões por módulo

Em vez de um sistema de "papéis" fixos (admin/gerente/etc.), cada usuário
tem, por empresa e por módulo (`financial`, `contacts`, `reports`,
`audit`, `admin`), um nível: `NONE`, `READ` ou `FULL`. Isso é uma tabela
simples (`permissions`) e um helper (`$user->can('financial', 'full')`)
usado nos controllers. Vantagem: dá pra ajustar acesso de qualquer
funcionário sem inventar um novo "cargo" no sistema toda vez.

## Auditoria

Toda escrita em tabela sensível (lançamentos financeiros, contatos,
permissões) passa por um trait `Auditable`, que registra automaticamente
em `audit_logs`: quem fez, quando, o quê mudou (antes/depois em JSON) e em
qual empresa. Isso não depende de o desenvolvedor lembrar de logar
manualmente — é automático a partir do momento que o model usa o trait.

## Multimoeda com taxa histórica

Cada lançamento financeiro (`financial_entries`) guarda a moeda usada e a
taxa de câmbio vigente **na data do lançamento** (não a taxa atual). Isso
vem de uma tabela `exchange_rates` com histórico por data — quando
precisar de um relatório em uma moeda-base (ex.: converter tudo pra USD),
o sistema usa a taxa daquele dia específico, não uma taxa "atual" que
distorceria relatórios de meses passados.

## Valores monetários

Todo valor monetário é `DECIMAL(15,4)` no banco (nunca `FLOAT`/`DOUBLE`),
porque ponto flutuante binário não representa exatamente frações
decimais — isso causa erros de centavos que se acumulam em relatórios
financeiros. Taxas de câmbio usam `DECIMAL(20,8)` pela precisão extra
que conversões monetárias exigem.

## Datas

Todas as datas são armazenadas e trafegam em ISO 8601 (`YYYY-MM-DD` ou
`YYYY-MM-DDTHH:MM:SSZ`), padrão nativo do Laravel/Carbon — evita
ambiguidade de formato (dia/mês trocado) entre BR e sistemas
internacionais.

## Lançamentos financeiros (`financial_entries`)

Uma única tabela modela despesa, receita, transferência e contas a
pagar/receber, diferenciadas pela coluna `type`:

- `expense` / `income`: usa `financial_account_id` (de onde saiu/entrou).
- `transfer`: usa `financial_account_id` (origem) e
  `destination_account_id` (destino).
- Toda entrada tem `due_date` (vencimento) e `paid_date` (nulo = ainda em
  aberto = é uma conta a pagar/receber; preenchido = já foi paga/recebida).

Essa modelagem única (em vez de 4 tabelas separadas) simplifica
relatórios que cruzam tipos (ex.: fluxo de caixa), ao custo de a tabela
ter algumas colunas que só fazem sentido para certos tipos — troca que
vale a pena aqui.

## Testes

Cada regra de negócio acima tem um teste automatizado correspondente em
`tests/Feature/` (isolamento entre empresas, bloqueio por permissão,
precisão decimal, taxa histórica, geração de log de auditoria). Rodar
`php artisan test` é a forma de verificar que nada quebrou — essa é a
rede de segurança combinada no briefing, no lugar de revisão manual de
código PHP.
