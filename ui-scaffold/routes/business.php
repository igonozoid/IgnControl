<?php

use App\Livewire\FinancialAccounts\Index as FinancialAccountsIndex;
use Illuminate\Support\Facades\Route;

// Rotas das telas de negócio (fora do que o Breeze gera). Este arquivo é
// carregado a partir de bootstrap/app.php (bloco `then:`).

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/financeiro/contas', FinancialAccountsIndex::class)->name('financial-accounts.index');
});
