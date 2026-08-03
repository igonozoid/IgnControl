<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users as AdminUsers;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersScreenTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company, string $adminLevel = 'full'): User
    {
        $admin = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($admin->id, ['role' => 'owner']);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'module' => 'admin',
            'level' => $adminLevel,
        ]);

        return $admin;
    }

    public function test_user_without_admin_full_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company, 'read');

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_new_user_with_no_access_by_default(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->set('name', 'Novo Usuário')
            ->set('email', 'novo@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('inviteUser');

        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);

        $newUser = User::query()->where('email', 'novo@example.com')->firstOrFail();

        $this->assertTrue($company->users()->where('users.id', $newUser->id)->exists());

        foreach (Permission::MODULES as $module) {
            $this->assertDatabaseHas('permissions', [
                'company_id' => $company->id,
                'user_id' => $newUser->id,
                'module' => $module,
                'level' => 'none',
            ]);
        }
    }

    public function test_admin_can_change_a_users_module_permission(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $member = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('editPermissions', $member->id)
            ->set('levels.financial', 'full')
            ->call('savePermissions');

        $this->assertDatabaseHas('permissions', [
            'company_id' => $company->id,
            'user_id' => $member->id,
            'module' => 'financial',
            'level' => 'full',
        ]);
    }

    public function test_admin_can_remove_a_user_from_the_company(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $member = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)->call('removeUser', $member->id);

        $this->assertFalse($company->users()->where('users.id', $member->id)->exists());
        $this->assertNull($member->fresh()->current_company_id);
    }

    public function test_admin_cannot_remove_themselves(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)->call('removeUser', $admin->id)->assertStatus(422);
    }

    public function test_users_list_is_scoped_to_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = $this->adminUser($companyA);

        $otherCompanyUser = User::factory()->create(['current_company_id' => $companyB->id, 'name' => 'Usuário Empresa B']);
        $companyB->users()->attach($otherCompanyUser->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)->assertDontSee('Usuário Empresa B');
    }
}
