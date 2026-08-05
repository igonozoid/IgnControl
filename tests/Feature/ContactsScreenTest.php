<?php

namespace Tests\Feature;

use App\Livewire\Contacts\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactsScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'contacts',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_list_only_shows_contacts_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        Contact::factory()->create(['company_id' => $companyA->id, 'name' => 'Cliente A']);
        Contact::factory()->create(['company_id' => $companyB->id, 'name' => 'Cliente B']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Cliente A')
            ->assertDontSee('Cliente B');
    }

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_and_delete(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Fornecedor Teste')
            ->set('is_supplier', true)
            ->call('save');

        $this->assertDatabaseHas('contacts', [
            'company_id' => $company->id,
            'name' => 'Fornecedor Teste',
            'is_supplier' => true,
        ]);

        $contact = Contact::query()->where('name', 'Fornecedor Teste')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $contact->id);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_full_access_user_can_save_birth_date_and_extra_fields(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Cliente Aniversariante')
            ->set('is_customer', true)
            ->set('birth_date', '1990-05-20')
            ->set('secondary_document', 'RG 12.345.678-9')
            ->set('district', 'Centro')
            ->call('save');

        $contact = Contact::query()->where('name', 'Cliente Aniversariante')->firstOrFail();

        $this->assertSame('1990-05-20', $contact->birth_date->toDateString());
        $this->assertSame('RG 12.345.678-9', $contact->secondary_document);
        $this->assertSame('Centro', $contact->district);
    }

    public function test_leaving_birth_date_blank_saves_null_not_empty_string(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Sem Nascimento')
            ->call('save');

        $contact = Contact::query()->where('name', 'Sem Nascimento')->firstOrFail();

        $this->assertNull($contact->birth_date);
    }
}
