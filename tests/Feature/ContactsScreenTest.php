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
}
