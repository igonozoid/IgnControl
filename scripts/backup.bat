@echo off
REM Backup diario do IgnControl (banco + documentos anexados).
REM Cadastrar no Agendador de Tarefas do Windows para rodar 1x por dia
REM de madrugada (ver docs/backup-restore.md para o passo a passo).

cd /d "%~dp0.."

php artisan backup:run
php artisan backup:clean
