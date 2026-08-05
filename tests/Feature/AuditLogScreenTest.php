<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogs\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogScreenTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'admin',
            'level' => 'full',
        ]);

        return $user;
    }

    public function test_user_without_admin_full_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_list_only_shows_logs_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = $this->adminUser($companyA);
        $this->actingAs($admin);

        $contactA = Contact::factory()->create(['company_id' => $companyA->id, 'name' => 'Contato A']);
        $contactB = Contact::factory()->create(['company_id' => $companyB->id, 'name' => 'Contato B']);

        Livewire::test(Index::class)
            ->assertSee('Contato #'.$contactA->id)
            ->assertDontSee('Contato #'.$contactB->id);
    }

    public function test_filtering_by_action_only_shows_matching_logs(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $this->actingAs($admin);

        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $contact->update(['name' => 'Nome Alterado']);

        // "Atualizado" também aparece como opção no <select> de filtro, então
        // não dá pra usar assertDontSee nele — a asserção real é que a
        // listagem fica vazia (só existe um log de "updated" no cenário).
        Livewire::test(Index::class)
            ->set('action', 'deleted')
            ->assertSee('Nenhum registro de auditoria');
    }

    public function test_print_view_is_blocked_without_admin_full_access(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $this->get(route('admin.audit.print'))->assertForbidden();
    }

    public function test_print_view_lists_filtered_logs(): void
    {
        $company = Company::factory()->create();
        $admin = $this->adminUser($company);
        $this->actingAs($admin);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'name' => 'Contato Impresso']);

        $this->get(route('admin.audit.print'))
            ->assertOk()
            ->assertSee('Contato #'.$contact->id);
    }
}
