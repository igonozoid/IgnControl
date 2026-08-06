<?php

namespace Tests\Feature;

use App\Livewire\Admin\Access;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessScreenTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company): User
    {
        $admin = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($admin->id, ['role' => 'owner']);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'module' => 'admin',
            'level' => 'full',
        ]);

        return $admin;
    }

    public function test_user_without_admin_full_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        $this->actingAs($user)
            ->get(route('admin.access.index'))
            ->assertForbidden();
    }

    public function test_only_companies_the_admin_fully_controls_are_manageable(): void
    {
        $companyA = Company::factory()->create(['name' => 'Empresa A']);
        $companyB = Company::factory()->create(['name' => 'Empresa B']);
        $admin = $this->adminUser($companyA);

        // Admin só tem "read" no admin da empresa B — não deve aparecer.
        $companyB->users()->attach($admin->id);
        Permission::query()->create([
            'company_id' => $companyB->id,
            'user_id' => $admin->id,
            'module' => 'admin',
            'level' => 'read',
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(Access::class);
        $companies = $component->viewData('companies');

        $this->assertTrue($companies->contains('id', $companyA->id));
        $this->assertFalse($companies->contains('id', $companyB->id));
    }

    public function test_admin_can_grant_a_module_level_to_a_user_in_a_manageable_company(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $target = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(Access::class)
            ->call('selectUser', $target->id)
            ->call('setLevel', $company->id, 'rural', 'full');

        $this->assertDatabaseHas('permissions', [
            'company_id' => $company->id,
            'user_id' => $target->id,
            'module' => 'rural',
            'level' => 'full',
        ]);
        $this->assertTrue($company->users()->where('users.id', $target->id)->exists());
    }

    public function test_zeroing_all_modules_removes_the_user_from_the_company(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $target = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($target->id);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $target->id,
            'module' => 'rural',
            'level' => 'full',
        ]);

        $this->actingAs($admin);

        Livewire::test(Access::class)
            ->call('selectUser', $target->id)
            ->call('setLevel', $company->id, 'rural', 'none');

        $this->assertFalse($company->users()->where('users.id', $target->id)->exists());
        $this->assertNull($target->fresh()->current_company_id);
    }

    public function test_admin_cannot_grant_access_to_a_company_they_do_not_fully_control(): void
    {
        $manageable = Company::factory()->create();
        $other = Company::factory()->create();
        $admin = $this->adminUser($manageable);
        $target = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(Access::class)
            ->call('selectUser', $target->id)
            ->call('setLevel', $other->id, 'financial', 'full')
            ->assertForbidden();
    }

    public function test_search_by_email_finds_a_user_outside_the_admins_companies(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $stranger = User::factory()->create(['email' => 'novo@example.com']);

        $this->actingAs($admin);

        Livewire::test(Access::class)
            ->set('searchEmail', 'novo@example.com')
            ->call('searchByEmail')
            ->assertSet('selectedUserId', $stranger->id);
    }

    public function test_admin_cannot_select_themselves(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);

        $this->actingAs($admin);

        Livewire::test(Access::class)->call('selectUser', $admin->id)->assertStatus(422);
    }
}
