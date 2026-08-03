<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Company;
use App\Models\Contact;
use App\Models\FinancialAccount;
use App\Models\FinancialEntry;
use App\Models\Permission;
use App\Models\Currency;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    private function userWithModules(Company $company, array $modules): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        foreach ($modules as $module => $level) {
            Permission::query()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'module' => $module,
                'level' => $level,
            ]);
        }

        return $user;
    }

    public function test_user_without_any_module_access_sees_the_generic_welcome(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithModules($company, ['financial' => 'none', 'agenda' => 'none']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertSee('Use o menu ao lado');
    }

    public function test_financial_kpis_are_shown_to_users_with_financial_access(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithModules($company, ['financial' => 'read']);
        $account = FinancialAccount::factory()->create(['company_id' => $company->id, 'opening_balance' => '1000.00']);

        FinancialEntry::factory()->create([
            'company_id' => $company->id,
            'financial_account_id' => $account->id,
            'type' => 'income',
            'status' => 'pending',
            'amount' => '250.00',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('Saldo em caixa')
            ->assertSee('1.000,00')
            ->assertSee('250,00');
    }

    public function test_financial_kpis_are_hidden_without_financial_access(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithModules($company, ['financial' => 'none', 'agenda' => 'full']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertDontSee('Saldo em caixa');
    }

    public function test_agenda_widget_is_shown_to_users_with_agenda_access(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithModules($company, ['agenda' => 'read']);
        Task::factory()->create(['company_id' => $company->id, 'title' => 'Ligar pro cliente', 'status' => 'pending']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('Tarefas pendentes')
            ->assertSee('Ligar pro cliente');
    }

    public function test_needs_review_nudge_shows_the_pending_count(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithModules($company, ['financial' => 'full']);
        Contact::factory()->create(['company_id' => $company->id, 'needs_review' => true]);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertSee('1 cadastro pendente de revisão');
    }
}
