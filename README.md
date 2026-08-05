<p align="center">
  <img src="public/images/ignf-logo-light.png" width="320" alt="IgnControl">
</p>

# IgnControl

Sistema de gestão financeira multiempresa e multimoeda, para uso interno em
rede local (LAN), com uma segunda fase prevista de acesso externo limitado
(PWA). Reescrita em Laravel do sistema legado (desktop, Python/PySide6) do
mesmo nome.

## Stack

- **Laravel 12** + **Livewire 3** (com Volt) para as telas — renderização no
  servidor, sem precisar de um front-end JavaScript separado.
- **Tailwind CSS** + **Alpine.js** para estilo e interações do lado do
  cliente (slide-overs, tema claro/escuro).
- **MySQL** em desenvolvimento/produção; a suíte de testes roda em SQLite em
  memória.

## Principais módulos

- **Financeiro**: contas financeiras, categorias, centros de custo,
  lançamentos (receitas/despesas/transferências), com recibo imprimível.
- **Contatos**: clientes e fornecedores.
- **Relatórios**: DRE, fluxo de caixa, contas a pagar/receber por contato,
  despesas/receitas por centro de custo.
- **Administração**: usuários e permissões por módulo (Financeiro,
  Contatos, Relatórios, Auditoria, Administração), em três níveis
  (nenhum/leitura/total), por empresa.
- **Auditoria**: log automático de criação/edição/exclusão nos principais
  modelos do sistema.

## Decisões de arquitetura

Ver [`ARQUITETURA.md`](ARQUITETURA.md) — explica em português simples como
funciona o multiempresa (global scope por `company_id`), o sistema de
permissões, e a trilha de auditoria.

## Rodando o projeto localmente

Ver [`SETUP.md`](SETUP.md) para o passo a passo completo (Composer, npm,
banco de dados, `.env`).

## Produção / acesso externo

Ver [`PRODUCAO.md`](PRODUCAO.md) — notas sobre LAN + túnel para acesso
externo, permissões de escrita remota, e como deixar o servidor no ar
sem depender de um `.bat` na inicialização.

Resumo:

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Testes

```bash
php artisan test
```

A suíte cobre autenticação, isolamento entre empresas, permissões,
auditoria, e todas as telas de Financeiro/Contatos/Relatórios.
