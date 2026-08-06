<?php

namespace Tests\Feature;

use App\Livewire\Admin\Companies;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCompaniesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id, ['role' => 'owner']);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'admin',
            'level' => 'full',
        ]);

        return $user;
    }

    public function test_user_without_admin_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        $this->actingAs($user);

        Livewire::test(Companies::class)->assertForbidden();
    }

    public function test_admin_can_edit_the_active_company(): void
    {
        $company = Company::factory()->create(['name' => 'Antigo Nome']);
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('edit', $company->id)
            ->set('name', 'Novo Nome')
            ->set('legal_name', 'Novo Nome Ltda')
            ->set('tax_id', '12.345.678/0001-99')
            ->set('base_currency_code', 'BRL')
            ->set('is_active', true)
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertSame('Novo Nome', $company->fresh()->name);
        $this->assertSame('Novo Nome Ltda', $company->fresh()->legal_name);
    }

    public function test_admin_can_create_a_new_company_and_it_becomes_the_active_one(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('create')
            ->set('name', 'Filial Nova')
            ->set('base_currency_code', 'BRL')
            ->call('save')
            ->assertSet('showForm', false);

        $newCompany = Company::query()->where('name', 'Filial Nova')->firstOrFail();

        $this->assertSame($newCompany->id, $user->fresh()->current_company_id);
        $this->assertTrue($newCompany->users()->where('users.id', $user->id)->exists());
        $this->assertSame(
            count(Permission::MODULES),
            Permission::query()->where('company_id', $newCompany->id)->where('user_id', $user->id)->where('level', 'full')->count()
        );
    }

    public function test_admin_cannot_edit_a_company_they_do_not_belong_to(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $otherCompany = Company::factory()->create();

        $this->actingAs($user);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Companies::class)->call('edit', $otherCompany->id);
    }
}
