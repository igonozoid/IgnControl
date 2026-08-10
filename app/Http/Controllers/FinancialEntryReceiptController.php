<?php

namespace App\Http\Controllers;

use App\Models\FinancialEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Documento final do recibo, pronto pra impressão — recebe os campos já
 * revisados pelo usuário no diálogo (App\Livewire\FinancialEntries\Receipt)
 * via querystring, e só cai pros dados originais do lançamento quando um
 * parâmetro não veio preenchido (acesso direto à URL, por exemplo).
 * FinancialEntry já vem filtrado pela empresa ativa (BelongsToCompany),
 * então o route-model-binding sozinho impede ver o recibo de um
 * lançamento de outra empresa (dá 404).
 */
class FinancialEntryReceiptController extends Controller
{
    public function print(Request $request, FinancialEntry $financialEntry): View
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);

        $financialEntry->load(['contact', 'company']);

        $isExpense = $financialEntry->type === 'expense';

        // Quem assina o recibo: numa despesa, quem recebeu o pagamento
        // (o contato); numa receita, a própria empresa, que é quem está
        // confirmando o recebimento — mesma regra do sistema legado.
        $signatureDocument = $isExpense
            ? (string) ($financialEntry->contact?->document ?? '')
            : (string) ($financialEntry->company->tax_id ?? '');

        return view('receipts.financial-entry', [
            'entry' => $financialEntry,
            'isExpense' => $isExpense,
            'partyLabel' => $isExpense ? 'Pagamento para' : 'Recebido de',
            'entityLabel' => $isExpense ? 'Emitido por' : 'Recebido por',
            'partyName' => $request->query('party') ?: ($financialEntry->contact?->name ?? '—'),
            'entityName' => $request->query('entity') ?: $financialEntry->company->name,
            'amount' => (float) str_replace(',', '.', (string) $request->query('amount', (string) $financialEntry->amount)),
            'date' => $request->query('date') ?: ($financialEntry->paid_date ?? $financialEntry->due_date)->toDateString(),
            'document' => $request->query('document') ?: (string) $financialEntry->document_number,
            'amountWords' => $request->query('words', ''),
            'reference' => $request->query('reference') ?: (string) $financialEntry->description,
            'notes' => $request->query('notes') ?: (string) $financialEntry->notes,
            'copies' => max(1, min(2, (int) $request->query('copies', 2))),
            'signatureDocument' => $signatureDocument,
        ]);
    }
}
