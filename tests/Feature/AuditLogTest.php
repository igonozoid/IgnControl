<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_contact_writes_an_audit_log_entry(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        // company_id => null para deixar o BelongsToCompany preencher
        // sozinho com a empresa ativa do usuário logado (é isso que
        // este teste quer verificar).
        $contact = Contact::factory()->create(['name' => 'Fornecedor Teste', 'company_id' => null]);

        $log = AuditLog::query()
            ->where('auditable_type', Contact::class)
            ->where('auditable_id', $contact->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($company->id, $log->company_id);
    }

    public function test_updating_a_contact_writes_before_and_after_values(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create(['name' => 'Nome Original', 'company_id' => null]);
        $contact->update(['name' => 'Nome Alterado']);

        $log = AuditLog::query()
            ->where('auditable_type', Contact::class)
            ->where('auditable_id', $contact->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Nome Original', $log->old_values['name']);
        $this->assertSame('Nome Alterado', $log->new_values['name']);
    }

    public function test_changing_a_permission_level_writes_an_audit_log_entry(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['current_company_id' => $company->id]);
        $member = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($admin);

        $permission = Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $member->id,
            'module' => 'financial',
            'level' => 'none',
        ]);

        $permission->update(['level' => 'full']);

        $log = AuditLog::query()
            ->where('auditable_type', Permission::class)
            ->where('auditable_id', $permission->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('none', $log->old_values['level']);
        $this->assertSame('full', $log->new_values['level']);
    }

    public function test_removing_a_users_permission_writes_an_audit_log_entry(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['current_company_id' => $company->id]);
        $member = User::factory()->create(['current_company_id' => $company->id]);
        $this->actingAs($admin);

        $permission = Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $member->id,
            'module' => 'financial',
            'level' => 'read',
        ]);

        $permission->delete();

        $log = AuditLog::query()
            ->where('auditable_type', Permission::class)
            ->where('auditable_id', $permission->id)
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('read', $log->old_values['level']);
    }
}
