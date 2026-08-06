# Comparação Legado (Python) x Laravel

Levantamento campo a campo dos formulários do sistema antigo vs. o atual,
pra decidir o que vale trazer. Cada item tem uma prioridade sugerida —
é só um chute educado, quem decide é você.

## Lançamentos / Transferências

- ~~**Transferência entre moedas diferentes**~~ — feito. Quando origem e
  destino são de moedas diferentes, o formulário pede "Valor de destino"
  e "Tarifa da operação" (a taxa de câmbio é calculada automaticamente
  entre os dois valores, só informativa). O saldo de cada conta já
  considera isso: a origem debita o valor + a tarifa, o destino credita
  o valor convertido, não o que saiu.
- **Nº do documento e observações na transferência** (Média). O legado
  tem campo de documento e de observações na transferência; a nossa não
  tem nenhum dos dois (só nos lançamentos de despesa/receita).
- Já implementado nessa rodada: nº de documento, data de competência e
  parcelamento nos lançamentos de despesa/receita — isso já cobre o que
  mais pesava.

## Categorias

- **Ativar/inativar categoria** (Alta). O modelo já tem o campo
  `is_active`, mas o formulário não expõe o checkbox — hoje a única forma
  de "aposentar" uma categoria é apagando (o que pode quebrar lançamentos
  antigos que referenciam ela). O legado tem um checkbox "Ativa" simples.
- Escopo "Entidade x Global" do legado (categoria compartilhada entre
  todas as empresas) não se aplica ao nosso modelo — aqui cada empresa já
  é isolada por `company_id`, então toda categoria já é "da entidade" por
  natureza. Não é um gap, é uma diferença de arquitetura mesmo.

## Centros de custo

- ~~**Aplica a despesas / aplica a receitas**~~ — feito. Dois checkboxes
  no cadastro; por padrão os dois marcados (comportamento antigo,
  continua igual pra quem não mexer). Um centro de custo que só aplica a
  receita não aparece mais no dropdown de despesa (e vice-versa) —
  mesmo padrão de "ativo" filtrando o dropdown do gap 1.
- ~~**Orçamento (despesa) e projeção (receita)**~~ — feito, só os campos
  (`expense_budget`/`revenue_projection`, opcionais). Ainda não tem
  relatório de orçado x realizado usando eles — fica pra quando surgir
  a necessidade concreta, como o doc já dizia.

## Contas financeiras

- **Ativar/inativar conta** (Alta). Mesmo caso das categorias: o modelo
  tem `is_active`, o formulário não expõe.
- Campo "compartilhada entre entidades" do legado não se aplica aqui,
  mesma razão do escopo de categoria.

## Moedas

- ~~**Tela de cadastro de moedas**~~ — feito. Menu Financeiro > Moedas:
  código (ISO 4217, travado depois de criado), nome, símbolo, casas
  decimais e ativa/inativa. Excluir uma moeda em uso por alguma conta
  financeira é bloqueado (mesma trava de chave estrangeira que já
  existia) — a orientação nesse caso é marcar como inativa.

## Contatos

- ~~**Pessoa física x jurídica explícito**~~ — feito. Rádio Física/
  Jurídica no cadastro (`document_type`), com inferência automática pelo
  tamanho do documento até o usuário escolher manualmente — depois disso
  vira uma escolha explícita e fica salva. É o que decide o botão
  "Busca Básica" agora. Contatos existentes foram migrados pela mesma
  regra de inferência.
- ~~**Foto do contato**~~ — feito. Upload/troca/remoção na aba Dados
  básicos, servida por uma rota autenticada (`contacts.photo`), não
  fica pública.
- ~~**"Data importante"**~~ — feito (`important_date` +
  `important_date_label`, rótulo obrigatório só quando a data é
  preenchida). Entra como lembrete anual na Agenda, mesmo mecanismo do
  aniversário (cor diferente pra não confundir).
- ~~**Contatos de departamento**~~ — feito. Na aba Referências, só para
  pessoa jurídica: sublista de pessoas de contato dentro da empresa
  (nome, cargo, ramal, e-mail), mesmo padrão de lista simples das
  referências comerciais/bancárias (recriada por completo a cada
  "Salvar").
- Os campos ligados a vínculo empregatício que existiam dentro do próprio
  ContactDialog do legado (CTPS, data de admissão/demissão, tipo de
  vínculo) já foram cobertos — de forma mais completa, inclusive — pelo
  módulo de RH que construímos.

## Agenda / Tarefas

- ~~**Recorrência**~~ — feito. Tarefa pode ser diária, semanal,
  quinzenal, mensal/bimestral/trimestral/semestral/anual/bianual (com dia
  do mês âncora) ou "personalizada" (só um lembrete em texto, não
  calculável). Ao concluir uma tarefa recorrente, a próxima ocorrência é
  gerada sozinha — igual ao legado, não é uma lista pré-gerada. Reabrir
  uma tarefa cuja próxima ocorrência já foi gerada e continua aberta é
  bloqueado, com aviso, pra não duplicar.
- **Prioridade** (Baixa). Campo de prioridade da tarefa; hoje não existe.
- **Vínculo com lançamento financeiro** (já temos) — o legado tinha um
  "tipo de vínculo/alvo" genérico (contato ou lançamento); nós já
  cobrimos contato e lançamento como vínculo direto, então esse ponto já
  está coberto.

## Estoque (módulo novo)

O legado nunca teve uma tela de Estoque de verdade separada — a lógica
vivia dentro do módulo de Vendas (`expansion_*.py`), com produtos ligados
a perfil de tributação e grupo do DRE. Como Vendas ainda não existe no
Laravel, construímos o Estoque como módulo **standalone** primeiro:

- Categorias de produto, Produtos (com "controla estoque" por produto,
  igual ao legado), Locais de estoque (multi-depósito), Movimentações
  (ledger append-only — saldo sempre somado na hora, nunca uma coluna
  de "quantidade atual", mesmo desenho do legado).
- `StockService` é a única porta de entrada pra mexer em saldo
  (`available`/`postMovement`/`transfer`/`reverseByReference`), com
  `reference_type`/`reference_id` polimórfico — pensado de propósito
  pra Vendas (e futuramente Rural) plugarem depois sem precisar mexer
  nessa classe, só chamar `postMovement()` com o reference deles.
- ~~**Fora do escopo por enquanto**~~ — feito junto com o módulo de
  Vendas (abaixo): perfil de tributação por produto, categoria
  sugerida (DRE), e os pedidos de venda já geram `sale_out`/
  `donation_out`/`return_in` automaticamente, com estorno no
  cancelamento.

## Vendas (módulo novo)

Pedidos de venda/doação/bônus/devolução, com status
rascunho→confirmado→liquidado→cancelado. Cada confirmação/liquidação
gera movimentação de estoque automática via `StockService` (só pra
produtos com "controla estoque"), e — só quando o tipo é "Venda" — um
lançamento de receita no Financeiro (pendente se confirmado, pago se
liquidado). Editar um pedido recria os itens e a movimentação de
estoque do zero (delete+insert), igual ao legado.

**Cancelar** um pedido não apaga nada: gera movimento de estorno no
estoque (preserva o histórico) e marca o lançamento financeiro
vinculado como `canceled` — um desvio deliberado do legado, que criava
uma despesa espelho pra vendas já recebidas; aqui usamos o status
`canceled` que os relatórios já sabem ignorar, mais simples e
consistente com o resto do sistema.

**Fora do escopo por enquanto**: nota fiscal/emissão, múltiplas
condições de pagamento por pedido (só gera um lançamento único hoje),
orçamento/proposta antes da venda virar pedido. A conta financeira e a
categoria da venda são escolhidas explicitamente no formulário — o
legado tentava adivinhar a "conta padrão", aqui seguimos o padrão do
resto do sistema (sempre explícito).

## Operação Rural (módulo novo)

O legado tinha um módulo rural genérico (propriedade → talhão → ativo →
atividade/ocorrência, com baixa de insumo no Estoque), mas foi
**desativado da navegação em 2026-08-03** por sair do escopo real — o
código ficou no repositório, parado, sem manutenção. Ele também nunca
modelou safra/plantio/produtividade: "Colheita" era só mais um tipo de
evento pontual, sem quantidade colhida associada, e não havia nenhuma
integração com o Financeiro.

Portamos o desenho básico (propriedades, talhões, ativos, atividades
com consumo de insumo, ocorrências sanitárias/operacionais) e
**adicionamos o conceito de safra** que o legado não tinha:

- `CropSeason` (safra) tem ciclo próprio — planejada → plantada → em
  desenvolvimento → colhida/cancelada — com data de plantio,
  previsão e data real de colheita, produtividade esperada e real.
- Marcar uma safra como colhida é uma ação dedicada (não um status
  digitado à mão): se a safra tiver um produto vinculado
  (`harvested_product_id`), gera automaticamente uma movimentação
  `harvest_in` no Estoque com a quantidade colhida — a colheita passa a
  existir como saldo, pronta pra Vendas escoar depois. Isso fecha um
  gap que o legado nunca teve.
- Atividades (`RuralActivity`) seguem o mesmo espírito do Estoque: só
  concluídas (`status=done`) baixam insumo de fato
  (`consumption_out`), e editar uma atividade recria os itens e a
  movimentação do zero (`StockService::reverseByReference` +
  repost) — diferente do legado, que apagava e reinseria sem
  nenhuma trava de saldo insuficiente.
- **Cancelar** uma atividade ou reabrir uma safra colhida estorna
  (apaga) a movimentação de estoque gerada — não é um contra-lançamento
  como em Vendas, porque aqui a movimentação nunca representou um fato
  gerador financeiro (é consumo/produção interna, não uma venda).

**Fora do escopo por enquanto**: relatório de custo por talhão/ativo
(o legado tinha isso pronto, calculado em tempo real por `SUM` sobre
`stock_movements` — dá pra portar depois sem mudar o schema, já que os
movimentos já carregam `total_cost`), integração automática com
Financeiro (o legado nunca teve isso, e não é óbvio que deveria ter —
o custo do insumo já aparece indiretamente no Estoque), e vínculo
formal entre uma venda e uma safra específica (quando a colheita vira
produto em estoque, a venda dela passa pelo módulo de Vendas genérico,
sem rastro direto pra safra de origem além do produto em si).

## Configuração da empresa (Entidade)

- O legado tem, por entidade, checkboxes de "módulos habilitados"
  (Vendas, Estoque, Operação Rural, RH) e idioma/moeda padrão da
  entidade. Isso não existe formalmente no sistema novo — hoje o que
  controla o que aparece pra cada usuário são as permissões por módulo
  (Admin > Usuários), não uma configuração por empresa. Não chega a ser
  um "gap" — é outra forma de resolver o mesmo problema — mas vale ter em
  mente quando Vendas/Estoque/Operação Rural entrarem: nesse momento faz
  sentido decidir se cada empresa liga/desliga o módulo, ou se isso
  continua só por permissão de usuário.

## Resumo — o que eu recomendaria priorizar primeiro

1. Ativar/inativar categoria, conta e centro de custo (são 3 checkboxes
   simples, o campo já existe no banco).
2. Transferência entre moedas diferentes (câmbio + tarifa) — só é urgente
   se vocês já fazem isso na prática; se todas as contas usadas hoje são
   em R$, pode esperar.
3. Recorrência de tarefas — conveniência boa, não bloqueia nada.
4. ~~O resto (foto, contatos de departamento, tela de moedas, orçamento
   por centro de custo, PF/PJ explícito)~~ — feito também. O que sobra
   deste levantamento são só os itens de arquitetura (escopo
   Entidade/Global, módulos habilitados por empresa) e os módulos novos
   (Vendas/Estoque/Operação Rural), que ficam pra quando entrarem
   mesmo.
