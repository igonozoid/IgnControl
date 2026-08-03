# Setup — Sistema Financeiro (Laravel + MySQL)

Este arquivo tem o passo a passo **exato** para colocar o projeto de pé no
seu PC com XAMPP. Eu não consigo rodar Composer/PHP no meu ambiente, então
esses comandos precisam ser executados por você (copiar e colar no
terminal). Qualquer erro, me cola a saída aqui que eu ajusto os arquivos.

## 0. Pré-requisitos

Abra um terminal (PowerShell) e confira:

```powershell
php -v
composer -V
```

- Se `php -v` não funcionar: adicione a pasta do PHP do XAMPP (ex.:
  `C:\xampp\php`) à variável de ambiente `PATH` do Windows, e abra um novo
  terminal.
- Se `composer -V` não funcionar: baixe e instale o Composer em
  https://getcomposer.org/download/ (instalador Windows, `Composer-Setup.exe`).
  O instalador detecta o PHP do XAMPP automaticamente.

## 1. Criar o esqueleto do Laravel

Dentro da pasta `_projects\IgnControl_Laravel` (que já está vazia e é o
projeto), rode:

```powershell
cd C:\Users\igono\Documents\_projects\IgnControl_Laravel
composer create-project laravel/laravel .
```

O `.` no final é importante — instala direto nesta pasta, sem criar uma
subpasta. Isso baixa o framework Laravel e todas as dependências (pasta
`vendor/`, que não entra no Git).

## 2. Aplicar os arquivos que eu já escrevi (o "scaffold" do sistema)

Eu preparei todo o código específico do sistema (migrations, models,
controllers, testes) numa pasta separada: `app-scaffold/`, já dentro
desta mesma pasta do projeto (`IgnControl_Laravel`). Ela espelha
exatamente a estrutura de pastas do Laravel. Também deixei um script
pronto, `apply-scaffold.ps1`, que copia tudo no lugar certo:

```powershell
cd C:\Users\igono\Documents\_projects\IgnControl_Laravel
.\apply-scaffold.ps1
```

(Se preferir fazer manualmente em vez do script: `robocopy app-scaffold\app app /E`,
e o mesmo para `database`, `tests` e `routes` — o `/E` copia tudo, inclusive
subpastas, sem apagar o que já existe.)

Depois de aplicado, pode apagar a pasta `app-scaffold/` e o
`apply-scaffold.ps1` — eles são só o "instalador" do código, não fazem
parte do projeto final.

## 2.1. Registrar o middleware de permissões e habilitar rotas de API

O sistema usa um middleware próprio (`App\Http\Middleware\EnsureModuleAccess`,
apelidado de `module`) para bloquear rotas conforme o nível de permissão do
usuário. Abra `bootstrap/app.php` (Laravel 11/12) e, dentro de
`->withMiddleware(function (Middleware $middleware) { ... })`, adicione:

```php
$middleware->alias([
    'module' => \App\Http\Middleware\EnsureModuleAccess::class,
]);
```

(Se o `composer create-project` instalar Laravel 10 ou anterior, em vez
disso me avise — nessa versão o registro é feito em
`app/Http/Kernel.php`, dentro de `$middlewareAliases`, e eu ajusto a
instrução.)

Como o `routes/api.php` não existe por padrão em instalações novas do
Laravel, rode também:

```powershell
php artisan install:api
```

Isso cria `routes/api.php` (que o Sanctum usa para autenticação de API) e
registra o arquivo — se ele perguntar para sobrescrever, **não
sobrescreva** o que veio do meu scaffold; se sobrescrever sem querer, é
só rodar o passo 2 (`robocopy ... routes routes /E`) de novo por cima.

## 3. Configurar o banco de dados (MySQL do XAMPP)

1. Abra o **phpMyAdmin** do XAMPP e crie um banco vazio, ex.: `ignfinance`.
2. Copie `.env.example` para `.env` (o `composer create-project` já cria
   isso, mas confirme que existe):

```powershell
copy .env.example .env
php artisan key:generate
```

3. Edite o `.env` e ajuste:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ignfinance
DB_USERNAME=root
DB_PASSWORD=
```

(usuário/senha padrão do MySQL do XAMPP é `root` sem senha, a menos que
você tenha alterado isso).

## 4. Migrar e popular dados de demonstração

```powershell
php artisan migrate
php artisan db:seed
```

Isso cria as tabelas e uma empresa de exemplo com um usuário admin, para
você já conseguir navegar/testar.

## 5. Rodar os testes automatizados

```powershell
php artisan test
```

Isso é o mais importante do ponto 2 do seu briefing: a rede de segurança
do projeto é essa suíte de testes, não revisão de código PHP linha a
linha. Sempre que eu adicionar ou mudar uma funcionalidade, vou também
adicionar/atualizar testes aqui — e você só precisa rodar este comando e
me colar o resultado.

## 6. Subir o servidor local

```powershell
php artisan serve
```

Acesse http://127.0.0.1:8000 no navegador.

---

## O que fazer depois

Quando terminar os passos acima, me avise o resultado (principalmente do
`php artisan test`). A partir daí eu continuo construindo em cima do que
já está migrado — próximo passo natural é telas (Blade ou API +
frontend) para os módulos Financeiro e Contatos.
