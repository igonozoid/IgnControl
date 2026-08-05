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

- **Aplica a despesas / aplica a receitas** (Média). O legado tem dois
  checkboxes pra dizer se um centro de custo vale só pra despesa, só pra
  receita, ou pras duas coisas. Hoje qualquer centro de custo aparece nos
  dois formulários sem distinção.
- **Orçamento (despesa) e projeção (receita)** (Baixa/Média). Campos de
  valor orçado por centro de custo — dá pra fazer relatório de
  orçado x realizado depois. Não é usado agora, mas é base pra um
  relatório futuro.

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
- **Foto do contato** (Baixa). O legado permite anexar uma foto por
  contato. Hoje não existe.
- **"Data importante"** (Baixa) — um campo de lembrete de data além do
  aniversário (ex: aniversário de fundação da empresa-cliente). Hoje só
  temos `birth_date`.
- **Contatos de departamento** (Baixa/Média) — pra contato jurídico, uma
  sublista de pessoas de contato dentro daquela empresa (nome, cargo,
  ramal, e-mail). Não existe hoje; pode ser útil se vocês lidam com
  empresas grandes onde o contato "físico" muda por área.
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
4. O resto (foto, contatos de departamento, tela de moedas, orçamento por
   centro de custo) fica pra quando surgir a necessidade concreta.
