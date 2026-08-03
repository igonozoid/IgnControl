# Próxima etapa — telas (login + Contas Financeiras)

## Por que Livewire

Decisão técnica (não precisa aprovar, só avisando por quê): vou usar
**Laravel Breeze + Livewire** para as telas. Isso significa: as páginas
são renderizadas pelo servidor (PHP), com só um pouquinho de JavaScript
por baixo (o Livewire) pra atualizar a tela sem recarregar a página
inteira — por exemplo, abrir um modal de "nova conta" ou salvar um
formulário sem piscar a tela. A alternativa seria montar um app
JavaScript separado (Vue/React) conversando com a API que já existe —
mais trabalho de manutenção pra um sistema interno de LAN, sem ganho
real aqui. O Breeze também já vem com tela de login pronta, testada e
mantida pela equipe do Laravel, então não preciso reinventar isso.

## 1. Instalar o Breeze

```powershell
composer require laravel/breeze --dev
php artisan breeze:install livewire
```

O segundo comando vai perguntar:

- **"Which testing framework do you prefer?"** → escolha **PHPUnit**
  (a suíte de testes que já existe usa PHPUnit; se escolher Pest ele
  pode reescrever `tests/`).
- Pode aceitar os padrões nas outras perguntas (dark mode: como
  preferir, tanto faz).

## 2. Instalar as dependências de front-end e compilar

```powershell
npm install
npm run build
```

(`npm run build` gera os arquivos finais de CSS/JS; para desenvolver com
recarregamento automático depois, use `npm run dev` num terminal
separado, deixando ele rodando.)

## 3. Aplicar as telas de negócio

Igual da última vez: deixei um `ui-scaffold/` com os arquivos das telas
específicas do sistema (fora do que o Breeze já gera) e um script pra
aplicar:

```powershell
powershell -ExecutionPolicy Bypass -File .\apply-ui-scaffold.ps1
```

## 4. Rodar os testes e subir o servidor

```powershell
php artisan test
php artisan serve
```

Acesse http://127.0.0.1:8000 — vai cair na tela de login do Breeze.
Use o usuário do seed: `admin@ignfinance.local` / senha `password`.

Depois de logado, deve aparecer no menu a opção **Contas Financeiras**
— essa é a primeira tela de negócio de fato, funcionando ponta a ponta
(listar, criar, editar, excluir contas de caixa/banco). As próximas
telas (Categorias, Contatos, Lançamentos) seguem o mesmo padrão — eu
vou construindo módulo por módulo a partir daqui.

Me avisa o resultado de cada comando (principalmente se o
`breeze:install` ou o `npm run build` derem algum erro — essas duas
etapas dependem do Node.js estar instalado no seu PC; se `npm` não for
reconhecido, me avisa que te passo o link de instalação).
