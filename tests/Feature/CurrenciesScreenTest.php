<?php

namespace Tests\Feature;

use App\Livewire\Currencies\Index;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrenciesScreenTest extends TestCase
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

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_a_currency(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('code', 'gbp')
            ->set('name', 'Libra esterlina')
            ->set('symbol', '£')
            ->set('decimals', '2')
            ->call('save');

        $this->assertDatabaseHas('currencies', [
            'code' => 'GBP', // salvo sempre em maiúsculo
            'name' => 'Libra esterlina',
            'symbol' => '£',
            'decimals' => 2,
            'is_active' => true,
        ]);
    }

    public function test_editing_a_currency_cannot_change_its_code(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        Currency::factory()->create(['code' => 'USD', 'name' => 'Dólar americano']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', 'USD')
            ->set('code', 'XXX') // tentativa de burlar, deve ser ignorada no save()
            ->set('name', 'Dólar dos EUA')
            ->set('decimals', '2')
            ->call('save');

        $this->assertDatabaseHas('currencies', ['code' => 'USD', 'name' => 'Dólar dos EUA']);
        $this->assertDatabaseMissing('currencies', ['code' => 'XXX']);
    }

    public function test_filter_by_status_only_shows_matching_currencies(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Currency::factory()->create(['code' => 'BRL', 'name' => 'Real', 'is_active' => true]);
        Currency::factory()->create(['code' => 'ARS', 'name' => 'Peso argentino', 'is_active' => false]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterStatus', 'inactive')
            ->assertSee('Peso argentino')
            ->assertDontSee('Real');
    }

    public function test_cannot_delete_a_currency_in_use_by_a_financial_account(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        Currency::factory()->create(['code' => 'USD', 'name' => 'Dólar americano']);
        FinancialAccount::factory()->create(['company_id' => $company->id, 'currency_code' => 'USD']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('delete', 'USD')
            ->assertSee('já existe conta financeira usando ela', false);

        $this->assertDatabaseHas('currencies', ['code' => 'USD']);
    }

    public function test_can_delete_a_currency_not_in_use(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        Currency::factory()->create(['code' => 'JPY', 'name' => 'Iene']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', 'JPY');

        $this->assertDatabaseMissing('currencies', ['code' => 'JPY']);
    }
}
