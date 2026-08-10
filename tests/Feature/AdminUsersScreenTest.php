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

    public function test_admin_can_edit_a_users_name_and_email(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $member = User::factory()->create(['current_company_id' => $company->id, 'name' => 'Nome Antigo', 'email' => 'antigo@example.com']);
        $company->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('editDetails', $member->id)
            ->set('editName', 'Nome Novo')
            ->set('editEmail', 'novo@example.com')
            ->call('saveDetails');

        $member->refresh();
        $this->assertSame('Nome Novo', $member->name);
        $this->assertSame('novo@example.com', $member->email);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $member = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('editDetails', $member->id)
            ->set('editPassword', 'novaSenha123')
            ->set('editPassword_confirmation', 'novaSenha123')
            ->call('saveDetails');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('novaSenha123', $member->refresh()->password));
    }

    public function test_leaving_the_password_blank_keeps_the_old_one(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $member = User::factory()->create(['current_company_id' => $company->id, 'password' => bcrypt('senhaOriginal')]);
        $company->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('editDetails', $member->id)
            ->set('editEmail', $member->email)
            ->call('saveDetails');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('senhaOriginal', $member->refresh()->password));
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

    // --- Painel "outras empresas" (substitui a antiga tela Admin\Access) ---

    public function test_admin_can_grant_access_to_a_user_in_another_company_they_manage(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = $this->adminUser($companyA);
        $this->adminUser2($admin, $companyB);
        $member = User::factory()->create(['current_company_id' => $companyA->id]);
        $companyA->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('openOtherCompaniesPanel', $member->id)
            ->call('setOtherCompanyLevel', $member->id, $companyB->id, 'financial', 'full');

        $this->assertDatabaseHas('permissions', [
            'company_id' => $companyB->id,
            'user_id' => $member->id,
            'module' => 'financial',
            'level' => 'full',
        ]);
        $this->assertTrue($companyB->users()->where('users.id', $member->id)->exists());
    }

    public function test_admin_cannot_grant_access_to_a_company_they_do_not_manage(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create(); // admin NÃO tem admin+full aqui
        $admin = $this->adminUser($companyA);
        $member = User::factory()->create(['current_company_id' => $companyA->id]);
        $companyA->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->call('openOtherCompaniesPanel', $member->id)
            ->call('setOtherCompanyLevel', $member->id, $companyB->id, 'financial', 'full')
            ->assertForbidden();
    }

    public function test_zeroing_all_modules_in_other_company_removes_the_user_from_it(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = $this->adminUser($companyA);
        $this->adminUser2($admin, $companyB);
        $member = User::factory()->create(['current_company_id' => $companyA->id]);
        $companyA->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin);

        $component = Livewire::test(AdminUsers::class)->call('openOtherCompaniesPanel', $member->id);
        $component->call('setOtherCompanyLevel', $member->id, $companyB->id, 'financial', 'full');
        $this->assertTrue($companyB->users()->where('users.id', $member->id)->exists());

        $component->call('setOtherCompanyLevel', $member->id, $companyB->id, 'financial', 'none');
        $this->assertFalse($companyB->users()->where('users.id', $member->id)->exists());
    }

    public function test_search_by_email_finds_a_user_outside_the_current_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = $this->adminUser($companyA);
        $this->adminUser2($admin, $companyB);

        $outsider = User::factory()->create(['email' => 'fora@example.com', 'current_company_id' => null]);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)
            ->set('searchEmail', 'fora@example.com')
            ->call('searchByEmail')
            ->assertSee('fora@example.com');
    }

    public function test_admin_cannot_open_their_own_other_companies_panel(): void
    {
        $companyA = Company::factory()->create();
        $admin = $this->adminUser($companyA);
        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)->call('openOtherCompaniesPanel', $admin->id)->assertStatus(422);
    }

    /**
     * Dá ao admin logado controle total ("admin"+"full") também numa
     * segunda empresa, sem trocar a empresa ativa dele — é o que faz
     * essa empresa aparecer no painel "outras empresas".
     */
    private function adminUser2(User $admin, Company $otherCompany): void
    {
        $otherCompany->users()->attach($admin->id, ['role' => 'owner']);

        Permission::query()->create([
            'company_id' => $otherCompany->id,
            'user_id' => $admin->id,
            'module' => 'admin',
            'level' => 'full',
        ]);
    }
}
