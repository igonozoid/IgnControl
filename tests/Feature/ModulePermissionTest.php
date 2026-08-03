<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithLevel(Company $company, string $module, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => $module,
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_permission_is_blocked_from_creating_a_financial_account(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = $this->makeUserWithLevel($company, 'financial', 'none');

        $response = $this->actingAs($user)->postJson('/api/financial-accounts', [
            'name' => 'Caixa',
            'type' => 'cash',
            'currency_code' => 'BRL',
        ]);

        $response->assertForbidden();
    }

    public function test_user_with_read_only_can_list_but_not_create(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = $this->makeUserWithLevel($company, 'financial', 'read');

        $this->actingAs($user)->getJson('/api/financial-accounts')->assertOk();

        $this->actingAs($user)->postJson('/api/financial-accounts', [
            'name' => 'Caixa',
            'type' => 'cash',
            'currency_code' => 'BRL',
        ])->assertForbidden();
    }

    public function test_user_with_full_access_can_create(): void
    {
        $company = Company::factory()->create();
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
        $user = $this->makeUserWithLevel($company, 'financial', 'full');

        $this->actingAs($user)->postJson('/api/financial-accounts', [
            'name' => 'Caixa',
            'type' => 'cash',
            'currency_code' => 'BRL',
        ])->assertCreated();
    }
}
