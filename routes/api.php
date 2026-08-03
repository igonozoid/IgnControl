<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialEntryController;
use Illuminate\Support\Facades\Route;

// NOTA: este arquivo é novo (o Laravel 11 não cria routes/api.php por
// padrão). Se `php artisan route:list` não mostrar essas rotas, rode:
//   php artisan install:api
// e depois cole este conteúdo de volta.

Route::middleware(['auth:sanctum'])->group(function () {

    // Leitura (READ ou FULL já dá acesso)
    Route::middleware('module:financial,read')->group(function () {
        Route::get('financial-accounts', [FinancialAccountController::class, 'index']);
        Route::get('financial-accounts/{financial_account}', [FinancialAccountController::class, 'show']);
        Route::get('financial-entries', [FinancialEntryController::class, 'index']);
        Route::get('financial-entries/{financial_entry}', [FinancialEntryController::class, 'show']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'show']);
    });

    // Escrita (exige FULL no módulo financeiro)
    Route::middleware('module:financial,full')->group(function () {
        Route::post('financial-accounts', [FinancialAccountController::class, 'store']);
        Route::put('financial-accounts/{financial_account}', [FinancialAccountController::class, 'update']);
        Route::delete('financial-accounts/{financial_account}', [FinancialAccountController::class, 'destroy']);

        Route::post('financial-entries', [FinancialEntryController::class, 'store']);
        Route::put('financial-entries/{financial_entry}', [FinancialEntryController::class, 'update']);
        Route::delete('financial-entries/{financial_entry}', [FinancialEntryController::class, 'destroy']);

        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

    // Contatos: módulo próprio
    Route::middleware('module:contacts,read')->group(function () {
        Route::get('contacts', [ContactController::class, 'index']);
        Route::get('contacts/{contact}', [ContactController::class, 'show']);
    });
    Route::middleware('module:contacts,full')->group(function () {
        Route::post('contacts', [ContactController::class, 'store']);
        Route::put('contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);
    });
});
