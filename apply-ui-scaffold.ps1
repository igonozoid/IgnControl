<#
Aplica os arquivos de ui-scaffold/ (telas Livewire) por cima do projeto,
depois de já ter rodado `php artisan breeze:install livewire` e
`npm run build`.

Uso (de dentro da raiz do projeto):
    .\apply-ui-scaffold.ps1
#>

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Error "Não encontrei o arquivo 'artisan' nesta pasta. Rode este script de dentro da raiz do projeto Laravel."
    exit 1
}

if (-not (Test-Path ".\ui-scaffold")) {
    Write-Error "Pasta ui-scaffold não encontrada aqui."
    exit 1
}

Write-Host "Copiando app/Livewire ..."
robocopy "ui-scaffold\app" "app" /E | Out-Null

Write-Host "Copiando resources/views/livewire ..."
robocopy "ui-scaffold\resources" "resources" /E | Out-Null

Write-Host "Copiando tests/ ..."
robocopy "ui-scaffold\tests" "tests" /E | Out-Null

Write-Host "Copiando routes/business.php ..."
robocopy "ui-scaffold\routes" "routes" /E | Out-Null

Write-Host ""
Write-Host "Pronto. Próximos passos:"
Write-Host "  1. Adicionar o link 'Contas Financeiras' e o componente <livewire:company-switcher />"
Write-Host "     na navegação (resources/views/layouts/navigation.blade.php) -- me avisa quando"
Write-Host "     chegar aqui, eu edito esse arquivo direto pra você."
Write-Host "  2. php artisan test"
Write-Host "  3. php artisan serve"
