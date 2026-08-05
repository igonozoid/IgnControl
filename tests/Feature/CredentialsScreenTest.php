<?php

namespace Tests\Feature;

use App\Livewire\Admin\Credentials\Index;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Credential;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CredentialsScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'credentials',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_credentials_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_list_only_shows_credentials_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        Credential::factory()->create(['company_id' => $companyA->id, 'title' => 'Credencial A']);
        Credential::factory()->create(['company_id' => $companyB->id, 'title' => 'Credencial B']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Credencial A')
            ->assertDontSee('Credencial B');
    }

    public function test_full_access_user_can_create_a_credential(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('category', 'login')
            ->set('title', 'SPC Brasil')
            ->set('url', 'https://www.spcbrasil.org.br/login')
            ->set('username', 'minhaempresa')
            ->set('password', 'segredo123')
            ->call('save');

        $this->assertDatabaseHas('credentials', [
            'company_id' => $company->id,
            'title' => 'SPC Brasil',
            'username' => 'minhaempresa',
        ]);

        $credential = Credential::query()->where('title', 'SPC Brasil')->firstOrFail();
        $this->assertSame('segredo123', $credential->password);
    }

    public function test_password_is_stored_encrypted_not_in_plain_text(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('title', 'Serasa')
            ->set('password', 'segredo123')
            ->call('save');

        $rawPassword = DB::table('credentials')->where('title', 'Serasa')->value('password');

        $this->assertNotSame('segredo123', $rawPassword);
        $this->assertStringNotContainsString('segredo123', $rawPassword);
    }

    public function test_full_access_user_can_edit_and_delete_a_credential(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $credential = Credential::factory()->create(['company_id' => $company->id, 'title' => 'Original']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $credential->id)
            ->set('title', 'Editado')
            ->call('save');

        $this->assertSame('Editado', $credential->refresh()->title);

        Livewire::test(Index::class)->call('delete', $credential->id);

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }

    public function test_revealing_a_password_writes_an_audit_log_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $credential = Credential::factory()->create(['company_id' => $company->id, 'password' => 'segredo123']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('reveal', $credential->id)
            ->assertSet('revealed', [$credential->id]);

        $log = AuditLog::query()
            ->where('auditable_type', Credential::class)
            ->where('auditable_id', $credential->id)
            ->where('action', 'viewed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_copying_a_password_writes_an_audit_log_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $credential = Credential::factory()->create(['company_id' => $company->id, 'password' => 'segredo123']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('logCopy', $credential->id)
            ->assertDispatched('credential-copied');

        $log = AuditLog::query()
            ->where('auditable_type', Credential::class)
            ->where('auditable_id', $credential->id)
            ->where('action', 'copied')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
    }
}
