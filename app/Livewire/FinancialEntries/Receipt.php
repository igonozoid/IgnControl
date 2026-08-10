<?php

namespace App\Livewire\FinancialEntries;

use App\Models\FinancialEntry;
use App\Support\ValorPorExtenso;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Diálogo de emissão de recibo — mesma ideia do "ReceiptDialog" do
 * sistema legado: antes de gerar o documento final, mostra os campos
 * padrão do recibo já preenchidos a partir do lançamento, mas
 * EDITÁVEIS (o recibo impresso não precisa bater 100% com o cadastro —
 * ex.: o nome de quem assina pode vir escrito diferente do contato).
 * Nada aqui é salvo no lançamento; "Visualizar recibo" só repassa os
 * valores editados pro documento final via querystring.
 */
#[Layout('layouts.app')]
class Receipt extends Component
{
    public FinancialEntry $entry;

    public string $partyName = '';
    public string $entityName = '';
    public string $amount = '';
    public string $date = '';
    public string $document = '';
    public string $amountWords = '';
    public string $reference = '';
    public string $notes = '';
    public string $copies = '2';

    public function mount(FinancialEntry $financialEntry): void
    {
        abort_unless(Auth::user()->hasModuleAccess('financial', 'read'), 403);

        $financialEntry->load(['contact', 'company']);
        $this->entry = $financialEntry;

        $this->partyName = $financialEntry->contact?->name ?? '';
        $this->entityName = $financialEntry->company->name;
        $this->amount = (string) $financialEntry->amount;
        $this->date = ($financialEntry->paid_date ?? $financialEntry->due_date)->toDateString();
        $this->document = (string) $financialEntry->document_number;
        $this->reference = (string) $financialEntry->description;
        $this->notes = (string) $financialEntry->notes;
        $this->recalculateWords();
    }

    public function getIsExpenseProperty(): bool
    {
        return $this->entry->type === 'expense';
    }

    public function getPartyLabelProperty(): string
    {
        return $this->isExpense ? 'Pagamento para' : 'Recebido de';
    }

    public function getEntityLabelProperty(): string
    {
        return $this->isExpense ? 'Emitido por' : 'Recebido por';
    }

    /** Recalcula "valor por extenso" a partir do campo Valor atual — chamado ao sair do campo (blur), não a cada tecla, pra não atropelar edição manual. */
    public function updatedAmount(): void
    {
        $this->recalculateWords();
    }

    public function recalculateWords(): void
    {
        $value = (float) str_replace(',', '.', $this->amount);
        $this->amountWords = ucfirst(ValorPorExtenso::porExtenso($value, $this->entry->currency_code));
    }

    public function render()
    {
        return view('livewire.financial-entries.receipt');
    }
}
