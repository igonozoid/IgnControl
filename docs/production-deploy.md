# Deploy em produção (TimoSRV)

Runbook pra colocar o IgnControl pra valer no servidor. É um roteiro pra
você rodar direto no TimoSRV (RDP) — eu não tenho acesso a essa máquina,
então cada passo tem o comando exato; se algo der erro, me manda a saída
completa que eu ajusto, do mesmo jeito que fizemos com as migrações e
testes.

Ordem recomendada: banco → aplicação → Apache → backup → Cloudflare
Tunnel → checklist final. Cada bloco só faz sentido depois do anterior
estar funcionando.

## 0. Pré-requisitos no TimoSRV

- XAMPP (ou Apache + PHP + MySQL avulsos) já instalado, com PHP ≥ 8.2 e
  extensões `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd` habilitadas em
  `php.ini`.
- Composer instalado globalmente (`composer -V` funcionando no cmd/PowerShell).
- Git instalado (`git --version`).
- Um usuário MySQL dedicado pro sistema (não usar `root` em produção).

## 1. Trazer o código pro servidor

```powershell
cd C:\xampp\htdocs
git clone <url-do-repositorio> IgnControl_Laravel
cd IgnControl_Laravel
composer install --no-dev --optimize-autoloader
```

`--no-dev` evita instalar ferramentas de teste/debug em produção (menor
superfície de ataque, deploy mais rápido).

## 2. Banco de dados

```sql
CREATE DATABASE igncontrol CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'igncontrol'@'localhost' IDENTIFIED BY '<<senha_forte>>';
GRANT ALL PRIVILEGES ON igncontrol.* TO 'igncontrol'@'localhost';
FLUSH PRIVILEGES;
```

Guarde essa senha — vai entrar no `.env` no próximo passo.

## 3. Configurar o `.env` de produção

```powershell
copy .env.production.example .env
```

Editar o `.env` e preencher todos os `<<...>>`:

- `APP_URL` — o domínio/subdomínio que vai apontar pro Cloudflare Tunnel
  (ex.: `https://sistema.suaempresa.com.br`).
- `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` — os do passo 2.
- `MAIL_*` — se já tiver um provedor de e-mail transacional (SendGrid,
  SES, etc.); se não tiver ainda, pode deixar `MAIL_MAILER=log` por
  enquanto e ajustar depois — só afeta e-mail de reset de senha e
  alertas de backup, não bloqueia o resto do sistema.
- `BACKUP_*` — ver `docs/backup-restore.md`, ajustar ao caminho real do
  `mysqldump.exe` e da pasta de destino no TimoSRV.

**Nunca reaproveite a `APP_KEY` do ambiente de dev.** Gerar uma nova
específica de produção:

```powershell
php artisan key:generate --force
```

`APP_DEBUG` já vem `false` no template — **confirme que ficou assim**
antes de seguir. Com `true`, qualquer erro do sistema mostra stack
trace completo (caminhos de arquivo, variáveis, às vezes até senha de
banco) pra qualquer visitante — é o item de segurança mais importante
desta lista.

## 4. Migrar e preparar a aplicação

```powershell
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`--force` é necessário porque `APP_ENV=production` bloqueia `migrate`
sem confirmação explícita (trava de segurança do próprio Laravel, pra
não rodar migração em produção sem querer).

Os três `:cache` deixam o sistema mais rápido (não recompila config,
rotas e views a cada requisição) — mas atenção: depois de qualquer
deploy futuro (novo `git pull`), é preciso rodar
`php artisan config:cache && php artisan route:cache && php artisan view:cache`
de novo, senão o sistema continua servindo a versão em cache antiga.

### Criar a primeira empresa e usuário admin

Não existe seeder de produção (o `DatabaseSeeder` é só pra dev). Duas
opções:

- **Pelo tinker** (mais controlado):
  ```powershell
  php artisan tinker
  ```
  ```php
  $company = \App\Models\Company::create(['legal_name' => 'Sua Empresa Ltda', 'base_currency_code' => 'BRL', 'is_active' => true]);
  $user = \App\Models\User::create(['name' => 'Seu Nome', 'email' => 'voce@empresa.com', 'password' => bcrypt('<<senha_forte_temporaria>>'), 'current_company_id' => $company->id]);
  foreach (\App\Models\Permission::MODULES as $module) {
      \App\Models\Permission::create(['company_id' => $company->id, 'user_id' => $user->id, 'module' => $module, 'level' => 'full']);
  }
  ```
  Troque a senha depois do primeiro login (Perfil > Alterar senha).

## 5. Apache como serviço do Windows

Pelo XAMPP Control Panel: coluna do Apache, marcar a caixinha de
"serviço" (ícone de check ao lado de Start) — isso instala o Apache como
serviço do Windows, então ele sobe sozinho no boot do servidor, sem
precisar abrir o Control Panel manualmente depois de um reinício.

Alternativa via linha de comando (se preferir, ou se o Control Panel
não estiver disponível):

```powershell
cd C:\xampp\apache\bin
httpd.exe -k install
net start Apache2.4
```

Configurar o VirtualHost: usar `deploy/apache-vhost.conf.example` como
base, copiar o conteúdo pra
`C:\xampp\apache\conf\extra\httpd-vhosts.conf`, ajustando o caminho do
`DocumentRoot` se o projeto não estiver exatamente em
`C:\xampp\htdocs\IgnControl_Laravel`. Confirmar que
`C:\xampp\apache\conf\httpd.conf` tem a linha
`Include conf/extra/httpd-vhosts.conf` sem `#` na frente.

Reiniciar o Apache depois de qualquer mudança de config:
```powershell
net stop Apache2.4
net start Apache2.4
```

Testar localmente no próprio servidor (antes do Cloudflare Tunnel
entrar em cena): abrir `http://localhost` no navegador do TimoSRV e
confirmar que a tela de login do IgnControl aparece.

## 6. Backup em produção

O pacote já está pronto no código (`config/backup.php`,
`docs/backup-restore.md`) — falta só ativar no TimoSRV:

1. Conferir que `DB_DUMP_BINARY_PATH` e `BACKUP_DISK_PATH` no `.env`
   apontam pros caminhos reais do servidor.
2. `php artisan config:clear && php artisan config:cache` (pra pegar os
   valores novos do `.env`).
3. Testar: `php artisan backup:run --only-db`, depois
   `php artisan backup:run` completo. Confirmar que o `.zip` apareceu em
   `storage/app/backups/IgnControl/`.
4. Agendar via Windows Task Scheduler apontando pra
   `scripts\backup.bat`, exatamente como descrito em
   `docs/backup-restore.md` — esse passo ficou pendente desde que o
   backup foi construído (só rodou local até agora).
5. **Importante**: o destino do backup configurado hoje é local
   (`storage/app/backups`, na mesma máquina/disco do banco). Isso
   protege contra erro humano (apagar um registro, corromper uma
   tabela) mas **não** protege contra falha física do servidor (disco
   queimado, servidor roubado, incêndio). Se for viável, vale copiar
   periodicamente esses `.zip` pra fora do TimoSRV (um HD externo, outro
   servidor, ou um serviço de nuvem) — isso não está automatizado ainda,
   é uma decisão em aberto sobre quanto investir nisso agora.

## 7. Cloudflare Tunnel

Permite acessar o sistema de fora via HTTPS sem abrir porta nenhuma no
roteador/firewall do TimoSRV — o tunnel faz uma conexão de saída do
servidor pra Cloudflare, e o tráfego chega por ali.

1. Criar conta na Cloudflare (se ainda não tiver) e adicionar o domínio
   que vai ser usado (ex.: `suaempresa.com.br`) — precisa migrar o DNS
   pra Cloudflare se ainda não estiver lá.
2. No TimoSRV, baixar o `cloudflared` pra Windows:
   https://github.com/cloudflare/cloudflared/releases (arquivo
   `cloudflared-windows-amd64.exe`) — renomear pra `cloudflared.exe` e
   colocar em `C:\cloudflared\`.
3. Autenticar:
   ```powershell
   cd C:\cloudflared
   .\cloudflared.exe tunnel login
   ```
   Abre o navegador, pede pra selecionar o domínio — autoriza.
4. Criar o túnel:
   ```powershell
   .\cloudflared.exe tunnel create igncontrol
   ```
   Anota o `<<TUNNEL_ID>>` que aparece na saída — é usado no
   `config.yml`.
5. Copiar `deploy/cloudflared-config.yml.example` pra
   `C:\ProgramData\cloudflared\config.yml`, preenchendo o `<<TUNNEL_ID>>`
   e o hostname real.
6. Apontar o DNS pro túnel:
   ```powershell
   .\cloudflared.exe tunnel route dns igncontrol <<seu-dominio-ou-subdominio>>
   ```
7. Instalar como serviço do Windows (sobe sozinho no boot, igual ao
   Apache):
   ```powershell
   .\cloudflared.exe service install
   ```
8. Testar: acessar `https://<<seu-dominio>>` de fora da rede do TimoSRV
   (celular com wifi desligado, por exemplo) e confirmar que chega na
   tela de login.

## 8. Checklist de segurança antes de liberar uso real

- [ ] `APP_DEBUG=false` confirmado no `.env` do servidor.
- [ ] `APP_KEY` gerada nova, específica de produção (não é a mesma do
      ambiente de dev).
- [ ] Senha do usuário MySQL de produção é forte e diferente de
      qualquer senha usada em dev/teste.
- [ ] Senha do primeiro usuário admin trocada após o primeiro login.
- [ ] `SESSION_SECURE_COOKIE=true` (só funciona com HTTPS — confirmar
      que o Cloudflare Tunnel já está no ar antes de ativar isso, senão
      o login trava).
- [ ] Acesso RDP ao TimoSRV restrito (senha forte, e — se possível —
      IP allowlist ou VPN em vez de RDP exposto direto na internet).
- [ ] Backup testado de ponta a ponta pelo menos uma vez (rodar
      `backup:run`, restaurar num banco de teste, conferir que os dados
      batem — ver `docs/backup-restore.md`).
- [ ] Firewall do Windows permitindo só as portas necessárias (o
      Cloudflare Tunnel não exige nenhuma porta de entrada aberta — se
      não há outro uso, pode fechar 80/443 pro mundo externo e deixar
      só localhost).

## 9. Teste final (smoke test)

Depois de tudo no ar:

1. Login com o usuário admin criado no passo 4.
2. Criar uma categoria, uma conta financeira, um lançamento de teste —
   conferir que salva e aparece na listagem.
3. Conferir o Dashboard (saldo, pendências).
4. Rodar `php artisan backup:run` uma vez a mais já em produção e
   confirmar o `.zip`.
5. Deletar os dados de teste criados no passo 2 (ou deixar como
   registro de que o smoke test rodou, se preferir).

Depois disso o sistema está pronto pra uso real. Qualquer erro em
qualquer passo acima, manda a mensagem completa (print ou texto) que eu
te ajudo a resolver.
