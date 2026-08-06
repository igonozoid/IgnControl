<?php

namespace Tests\Feature;

use App\Livewire\RuralProperties\Index as RuralPropertiesIndex;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RH/Estoque/Vendas/Rural são "opcionais": além da permissão do
 * usuário, a empresa precisa ter o módulo marcado em
 * enabled_modules. Um não substitui o outro.
 */
class CompanyModuleActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_permission_is_not_enough_when_company_has_the_module_disabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => []]);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'rural',
            'level' => 'full',
        ]);

        $this->assertFalse($user->hasModuleAccess('rural', 'read'));
        $this->assertFalse($user->hasModuleAccess('rural', 'full'));
    }

    public function test_access_is_granted_once_the_company_enables_the_module_too(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['rural']]);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'rural',
            'level' => 'full',
        ]);

        $this->assertTrue($user->hasModuleAccess('rural', 'full'));
    }

    public function test_core_modules_are_always_enabled_regardless_of_company_setting(): void
    {
        $company = Company::factory()->create(['enabled_modules' => []]);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'financial',
            'level' => 'full',
        ]);

        $this->assertTrue($user->hasModuleAccess('financial', 'full'));
    }

    public function test_rural_screen_is_blocked_when_the_company_does_not_have_the_module_enabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => []]);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'rural',
            'level' => 'full',
        ]);

        $this->actingAs($user);

        Livewire::test(RuralPropertiesIndex::class)->assertForbidden();
    }
}
