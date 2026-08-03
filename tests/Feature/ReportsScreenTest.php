<?php

namespace Tests\Feature;

use App\Livewire\Reports\AccountStatement;
use App\Livewire\Reports\Analytical;
use App\Livewire\Reports\CashFlow;
use App\Livewire\Reports\CashForecast;
use App\Livewire\Reports\Dre;
use App\Livewire\Reports\CostCenters;
use App\Livewire\Reports\Payables;
use App\Livewire\Reports\Receivables;
use App\Livewire\Reports\Registrations;
use App\Models\Category;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'reports',
            'level' => $level,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    public function test_user_without_reports_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'reports',
            'level' => 'none',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_dre_sums_income_and_expense_by_category_and_computes_result(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $sales = Category::factory()->create(['company_id' => $company->id, 'name' => 'Vendas']);
        $rent = Category::factory()->create(['company_id' => $company->id, 'name' => 'Aluguel']);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'category_id' => $sales->id,
            'type' => 'income',
            'amount' => '1000.00',
            'due_date' => now()->toDateString(),
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'category_id' => $rent->id,
            'type' => 'expense',
            'amount' => '400.00',
            'due_date' => now()->toDateString(),
        ]);
        // Fora do período - não deve entrar na soma.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'category_id' => $sales->id,
            'type' => 'income',
            'amount' => '999.00',
            'due_date' => now()->subYear()->toDateString(),
        ]);

        $this->actingAs($user);

        Livewire::test(Dre::class)
            ->set('from', now()->startOfMonth()->toDateString())
            ->set('to', now()->toDateString())
            ->assertSee('Vendas')
            ->assertSee('Aluguel')
            ->assertSee('1.000,00')
            ->assertSee('400,00')
            ->assertSee('600,00') // resultado
            ->assertDontSee('999');
    }

    public function test_dre_is_scoped_to_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        $accountA = FinancialAccount::factory()->create(['company_id' => $companyA->id]);
        $accountB = FinancialAccount::factory()->create(['company_id' => $companyB->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id, 'name' => 'Categoria B']);

        FinancialEntry::factory()->create([
            'company_id' => $companyB->id,
            'financial_account_id' => $accountB->id,
            'category_id' => $categoryB->id,
            'type' => 'income',
            'amount' => '500.00',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($user);

        Livewire::test(Dre::class)->assertDontSee('Categoria B');
    }

    public function test_cash_flow_computes_opening_daily_and_closing_balance(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);

        $from = '2026-01-10';
        $to = '2026-01-20';

        // Saldo inicial: pago antes do período.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'amount' => '1000.00',
            'status' => 'paid',
            'due_date' => '2026-01-01',
            'paid_date' => '2026-01-01',
        ]);

        // Movimento dentro do período.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => '300.00',
            'status' => 'paid',
            'due_date' => '2026-01-15',
            'paid_date' => '2026-01-15',
        ]);

        // Pendente: não deve entrar em nenhuma soma (não foi pago).
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => '5000.00',
            'status' => 'pending',
            'due_date' => '2026-01-15',
        ]);

        $this->actingAs($user);

        Livewire::test(CashFlow::class)
            ->set('from', $from)
            ->set('to', $to)
            ->assertSee('1.000,00') // saldo inicial
            ->assertSee('700,00')   // saldo final (1000 - 300)
            ->assertDontSee('5.000,00');
    }

    public function test_payables_groups_open_expenses_by_contact_and_flags_overdue(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $supplier = Contact::factory()->create(['company_id' => $company->id, 'name' => 'Fornecedor X', 'is_supplier' => true]);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'contact_id' => $supplier->id,
            'type' => 'expense',
            'amount' => '200.00',
            'status' => 'pending',
            'description' => 'Material atrasado',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        // Já pago - não deve entrar no relatório de contas em aberto.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'contact_id' => $supplier->id,
            'type' => 'expense',
            'amount' => '999.00',
            'status' => 'paid',
            'due_date' => now()->subDays(5)->toDateString(),
            'paid_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->actingAs($user);

        Livewire::test(Payables::class)
            ->assertSee('Fornecedor X')
            ->assertSee('200,00')
            ->assertDontSee('999,00');
    }

    public function test_receivables_groups_open_income_by_contact(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $customer = Contact::factory()->create(['company_id' => $company->id, 'name' => 'Cliente Y', 'is_customer' => true]);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'contact_id' => $customer->id,
            'type' => 'income',
            'amount' => '350.00',
            'status' => 'pending',
            'description' => 'Serviço prestado',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($user);

        Livewire::test(Receivables::class)
            ->assertSee('Cliente Y')
            ->assertSee('350,00');
    }

    public function test_cost_centers_sums_income_and_expense_by_cost_center_in_period(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);
        $sales = CostCenter::factory()->create(['company_id' => $company->id, 'name' => 'Comercial']);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'cost_center_id' => $sales->id,
            'type' => 'income',
            'amount' => '800.00',
            'due_date' => '2026-02-10',
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'cost_center_id' => $sales->id,
            'type' => 'expense',
            'amount' => '250.00',
            'due_date' => '2026-02-15',
        ]);
        // Fora do período.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'cost_center_id' => $sales->id,
            'type' => 'income',
            'amount' => '999.00',
            'due_date' => '2025-01-01',
        ]);

        $this->actingAs($user);

        Livewire::test(CostCenters::class)
            ->set('from', '2026-02-01')
            ->set('to', '2026-02-28')
            ->assertSee('Comercial')
            ->assertSee('800,00')
            ->assertSee('250,00')
            ->assertDontSee('999,00');
    }

    public function test_analytical_lists_entries_in_period_with_totals(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'amount' => '500.00',
            'description' => 'Venda avulsa',
            'due_date' => '2026-03-10',
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => '120.00',
            'description' => 'Compra de material',
            'due_date' => '2026-03-12',
        ]);

        $this->actingAs($user);

        Livewire::test(Analytical::class)
            ->set('from', '2026-03-01')
            ->set('to', '2026-03-31')
            ->assertSee('Venda avulsa')
            ->assertSee('Compra de material')
            ->assertSee('500,00')
            ->assertSee('120,00');
    }

    public function test_cash_forecast_projects_balance_from_pending_entries(): void
    {
        $this->travelTo('2026-03-01');

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id]);

        // Já realizado - compõe o saldo atual.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'amount' => '1000.00',
            'status' => 'paid',
            'due_date' => '2026-02-01',
            'paid_date' => '2026-02-01',
        ]);

        // Pendente dentro do horizonte de projeção.
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => '300.00',
            'status' => 'pending',
            'due_date' => '2026-03-10',
        ]);

        $this->actingAs($user);

        Livewire::test(CashForecast::class)
            ->set('to', '2026-03-31')
            ->assertSee('1.000,00') // saldo atual realizado
            ->assertSee('700,00');  // saldo projetado final (1000 - 300)

        $this->travelBack();
    }

    public function test_account_statement_shows_running_balance_for_the_selected_account(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $account = FinancialAccount::factory()->create(['company_id' => $company->id, 'name' => 'Conta Principal']);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'amount' => '600.00',
            'status' => 'paid',
            'description' => 'Recebimento',
            'due_date' => '2026-04-05',
            'paid_date' => '2026-04-05',
        ]);
        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => '150.00',
            'status' => 'paid',
            'description' => 'Pagamento',
            'due_date' => '2026-04-06',
            'paid_date' => '2026-04-06',
        ]);

        $this->actingAs($user);

        Livewire::test(AccountStatement::class)
            ->set('accountId', $account->id)
            ->set('from', '2026-04-01')
            ->set('to', '2026-04-30')
            ->assertSee('Recebimento')
            ->assertSee('Pagamento')
            ->assertSee('450,00'); // saldo final (600 - 150)
    }

    public function test_registrations_report_lists_the_selected_registry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        CostCenter::factory()->create(['company_id' => $company->id, 'name' => 'Centro Alpha']);

        $this->actingAs($user);

        Livewire::test(Registrations::class)
            ->set('type', 'cost-centers')
            ->assertSee('Centro Alpha');
    }
}
