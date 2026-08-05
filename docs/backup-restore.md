# Backup e restauração

O sistema faz backup do **banco de dados (MySQL)** e dos **documentos
anexados aos contatos** (PDFs de consulta CNPJ, documentos de contato),
usando o pacote [spatie/laravel-backup](https://spatie.be/docs/laravel-backup).

O resultado de cada rodada é um único `.zip` salvo em
`storage/app/backups/IgnControl/` (ou no caminho definido em
`BACKUP_DISK_PATH` no `.env`).

## Retenção

- Todos os backups dos últimos 7 dias.
- Depois disso, 1 por dia até completar 30 dias.
- Depois disso, 1 por semana até completar 8 semanas.
- Depois disso, 1 por mês, guardado por 12 meses.
- Se a pasta de backups passar de 10 GB, os mais antigos são apagados
  primeiro, antes de qualquer uma das regras acima.

Essas regras rodam automaticamente todo dia (`php artisan backup:clean`,
já incluso em `scripts/backup.bat`).

## Configuração inicial (rodar uma vez)

1. `composer require spatie/laravel-backup`
2. No `.env`, adicionar (ajustar os caminhos ao seu ambiente):
   ```
   DB_DUMP_BINARY_PATH="C:\xampp\mysql\bin\"
   BACKUP_DISK_PATH="C:\xampp\htdocs\IgnControl_Laravel\storage\app\backups"
   BACKUP_NOTIFICATION_EMAIL="seu-email@exemplo.com"
   ```
   - `DB_DUMP_BINARY_PATH` é a pasta onde está o `mysqldump.exe` do XAMPP
     (não fica no PATH do Windows por padrão).
   - `BACKUP_NOTIFICATION_EMAIL` é opcional — só recebe e-mail se um
     backup falhar ou passar do prazo (precisa de `MAIL_*` configurado
     no `.env`; sem isso configurado, o backup roda normalmente, só não
     manda aviso por e-mail).
3. `php artisan config:clear`
4. Testar: `php artisan backup:run --only-db` (mais rápido, só banco) e
   depois `php artisan backup:run` (completo). Confirmar que apareceu um
   `.zip` na pasta de destino.

## Agendar (Windows Task Scheduler)

1. Abrir o **Agendador de Tarefas** do Windows.
2. Criar tarefa básica → nome "IgnControl - Backup diário".
3. Disparador: diariamente, num horário de baixo uso (ex: 02:00).
4. Ação: iniciar programa → apontar para
   `C:\xampp\htdocs\IgnControl_Laravel\scripts\backup.bat`
   (ajustar o caminho conforme onde o projeto estiver instalado).
5. Marcar "Executar mesmo se o usuário não estiver conectado", se
   disponível.

## Como restaurar um backup

1. Copiar o `.zip` do backup desejado para uma pasta local e
   descompactá-lo. Dentro dele:
   - `db-dumps/mysql-....sql` (ou `.gz`) — dump do banco.
   - `app/private/...` — os documentos anexados, na mesma estrutura de
     pastas usada pelo disco `local` (`storage/app/private`).
2. **Banco de dados**: restaurar o dump num banco (de preferência um
   banco novo/de teste primeiro, pra conferir antes de sobrescrever o
   banco em uso):
   ```
   mysql -u root -p nome_do_banco < caminho\para\database.sql
   ```
3. **Documentos**: copiar o conteúdo de `app/private` de volta para
   `storage/app/private` do projeto (sobrescrevendo ou mesclando,
   conforme o caso).
4. Rodar `php artisan config:clear` e conferir se o sistema abre
   normalmente e os dados/documentos restaurados aparecem.

Se o dump estiver comprimido (`.gz`), descompactar antes com um utilitário
como o 7-Zip antes de importar com `mysql`.
