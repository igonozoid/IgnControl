<?php

namespace App\Http\Controllers;

use App\Models\FinancialEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Recibo imprimível de um lançamento — mesma ideia do "Recibo" que
 * existia no sistema legado. FinancialEntry já vem filtrado pela empresa
 * ativa (BelongsToCompany), então o route-model-binding sozinho impede
 * ver o recibo de um lançamento de outra empresa (dá 404).
 */
class FinancialEntryReceiptController extends Controller
{
    public function show(FinancialEntry $financialEntry): View
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);

        $financialEntry->load(['financialAccount', 'destinationAccount', 'contact', 'category', 'costCenter', 'currency', 'company']);

        return view('receipts.financial-entry', ['entry' => $financialEntry]);
    }
}
