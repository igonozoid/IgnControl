<?php

namespace Tests\Feature;

use App\Livewire\FinancialEntries\Receipt as ReceiptDialog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * O recibo tem dois estágios, igual ao sistema legado: primeiro um
 * diálogo editável (Livewire, rota financial-entries.receipt) com os
 * campos padrão já preenchidos a partir do lançamento; só depois o
 * documento final pra impressão (financial-entries.receipt.print), que
 * usa os valores vindos do diálogo em vez de reconsultar o lançamento.
 */
class FinancialEntryReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'financial',
            'level' => $level,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    // --- Diálogo (App\Livewire\FinancialEntries\Receipt) ---

    public function test_dialog_pre_fills_fields_from_the_entry(): void
    {
        $company = Company::factory()->create(['name' => 'Empresa Teste']);
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'name' => 'Fornecedor X']);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'contact_id' => $contact->id,
            'type' => 'expense',
            'description' => 'Aluguel de agosto',
            'notes' => 'Pago via PIX',
            'amount' => '1234.56',
        ]);

        $this->actingAs($user);

        Livewire::test(ReceiptDialog::class, ['financialEntry' => $entry])
            ->assertSet('partyName', 'Fornecedor X')
            ->assertSet('entityName', 'Empresa Teste')
            ->assertSet('reference', 'Aluguel de agosto')
            ->assertSet('notes', 'Pago via PIX')
            ->assertSee('Pagamento para')
            ->assertSee('Mil, duzentos e trinta e quatro reais e cinquenta e seis centavos');
    }

    public function test_dialog_uses_recebido_de_label_for_income(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
        ]);

        $this->actingAs($user);

        Livewire::test(ReceiptDialog::class, ['financialEntry' => $entry])
            ->assertSee('Recebido de')
            ->assertSee('Recebido por');
    }

    public function test_recalculating_words_reflects_edited_amount(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'amount' => '100.00',
        ]);

        $this->actingAs($user);

        Livewire::test(ReceiptDialog::class, ['financialEntry' => $entry])
            ->set('amount', '50.00')
            ->call('recalculateWords')
            ->assertSet('amountWords', 'Cinquenta reais');
    }

    public function test_dialog_is_blocked_without_access(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt', $entry))
            ->assertForbidden();
    }

    public function test_dialog_for_entry_from_another_company_is_not_found(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        $accountB = FinancialAccount::factory()->create(['company_id' => $companyB->id]);
        $entryB = FinancialEntry::factory()->create([
            'company_id' => $companyB->id,
            'financial_account_id' => $accountB->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt', $entryB))
            ->assertNotFound();
    }

    // --- Documento final (FinancialEntryReceiptController@print) ---

    public function test_print_view_uses_values_from_the_querystring_not_the_stored_entry(): void
    {
        $company = Company::factory()->create(['name' => 'Empresa Teste']);
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Descrição original',
            'amount' => '1200.00',
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt.print', $entry).'?'.http_build_query([
                'party' => 'Nome Editado',
                'reference' => 'Referência editada no diálogo',
                'words' => 'Mil e duzentos reais',
                'copies' => 1,
            ]))
            ->assertOk()
            ->assertSee('Empresa Teste')
            ->assertSee('Nome Editado')
            ->assertSee('Referência editada no diálogo')
            ->assertSee('Mil e duzentos reais')
            ->assertDontSee('Descrição original');
    }

    public function test_print_view_falls_back_to_entry_data_without_query_params(): void
    {
        $company = Company::factory()->create(['name' => 'Empresa Teste']);
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Aluguel de agosto',
            'amount' => '1200.00',
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt.print', $entry))
            ->assertOk()
            ->assertSee('Empresa Teste')
            ->assertSee('Aluguel de agosto')
            ->assertSee('1.200,00');
    }

    public function test_print_view_shows_two_vias_by_default(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt.print', $entry))
            ->assertOk()
            ->assertSee('1ª via')
            ->assertSee('2ª via');
    }

    public function test_print_view_is_blocked_without_access(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $entry = FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt.print', $entry))
            ->assertForbidden();
    }

    public function test_print_view_for_entry_from_another_company_is_not_found(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        $accountB = FinancialAccount::factory()->create(['company_id' => $companyB->id]);
        $entryB = FinancialEntry::factory()->create([
            'company_id' => $companyB->id,
            'financial_account_id' => $accountB->id,
        ]);

        $this->actingAs($user)
            ->get(route('financial-entries.receipt.print', $entryB))
            ->assertNotFound();
    }
}
