<?php

namespace Tests\Feature;

use App\Livewire\Contacts\Form;
use App\Livewire\Contacts\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Credential;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        Livewire::test(Form::class)->assertForbidden();
    }

    public function test_full_access_user_can_create_and_delete(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
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

        Livewire::test(Form::class)
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

        Livewire::test(Form::class)
            ->set('name', 'Sem Nascimento')
            ->call('save');

        $contact = Contact::query()->where('name', 'Sem Nascimento')->firstOrFail();

        $this->assertNull($contact->birth_date);
    }

    public function test_full_access_user_can_save_full_address(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Endereço Completo')
            ->set('city', 'Curitiba')
            ->set('state', 'PR')
            ->set('postal_code', '80000-000')
            ->set('country', 'Brasil')
            ->call('save');

        $contact = Contact::query()->where('name', 'Cliente Endereço Completo')->firstOrFail();

        $this->assertSame('Curitiba', $contact->city);
        $this->assertSame('PR', $contact->state);
        $this->assertSame('80000-000', $contact->postal_code);
        $this->assertSame('Brasil', $contact->country);
    }

    public function test_full_access_user_can_save_credit_profile(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Com Crédito')
            ->set('purchase_frequency', 'Mensal')
            ->set('classification', 'A')
            ->set('credit_limit', '5000.50')
            ->set('credit_checked', true)
            ->set('credit_check_date', '2026-01-10')
            ->set('has_credit_issue', true)
            ->set('credit_issue_location', 'SPC')
            ->set('mother_name', 'Maria da Silva')
            ->call('save');

        $contact = Contact::query()->where('name', 'Cliente Com Crédito')->firstOrFail();

        $this->assertSame('Mensal', $contact->purchase_frequency);
        $this->assertSame('A', $contact->classification);
        $this->assertSame('5000.50', $contact->credit_limit);
        $this->assertTrue($contact->credit_checked);
        $this->assertSame('2026-01-10', $contact->credit_check_date->toDateString());
        $this->assertTrue($contact->has_credit_issue);
        $this->assertSame('SPC', $contact->credit_issue_location);
        $this->assertSame('Maria da Silva', $contact->mother_name);
    }

    public function test_credit_check_date_is_required_when_credit_checked_is_true(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Sem Data')
            ->set('credit_checked', true)
            ->call('save')
            ->assertHasErrors(['credit_check_date']);
    }

    public function test_credit_issue_location_is_required_when_has_credit_issue_is_true(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Sem Local')
            ->set('has_credit_issue', true)
            ->call('save')
            ->assertHasErrors(['credit_issue_location']);
    }

    public function test_full_access_user_can_add_commercial_and_bank_references(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Com Referências')
            ->call('addCommercialReferenceRow')
            ->set('commercialReferenceRows.0.name', 'Fornecedor Amigo')
            ->set('commercialReferenceRows.0.phone', '11999990000')
            ->call('addBankReferenceRow')
            ->set('bankReferenceRows.0.bank', 'Banco X')
            ->set('bankReferenceRows.0.agency', '0001')
            ->set('bankReferenceRows.0.account', '12345-6')
            ->call('addContactBankAccountRow')
            ->set('contactBankAccountRows.0.bank', 'Banco Y')
            ->set('contactBankAccountRows.0.holder', 'Cliente Com Referências')
            ->call('save');

        $contact = Contact::query()->where('name', 'Cliente Com Referências')->firstOrFail();

        $this->assertDatabaseHas('commercial_references', [
            'contact_id' => $contact->id,
            'name' => 'Fornecedor Amigo',
            'phone' => '11999990000',
        ]);
        $this->assertDatabaseHas('bank_references', [
            'contact_id' => $contact->id,
            'bank' => 'Banco X',
            'agency' => '0001',
            'account' => '12345-6',
        ]);
        $this->assertDatabaseHas('contact_bank_accounts', [
            'contact_id' => $contact->id,
            'bank' => 'Banco Y',
            'holder' => 'Cliente Com Referências',
        ]);
    }

    public function test_removing_a_reference_row_before_saving_does_not_persist_it(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Cliente Removeu Referência')
            ->call('addCommercialReferenceRow')
            ->set('commercialReferenceRows.0.name', 'Vai Sair')
            ->call('addCommercialReferenceRow')
            ->set('commercialReferenceRows.1.name', 'Vai Ficar')
            ->call('removeCommercialReferenceRow', 0)
            ->call('save');

        $contact = Contact::query()->where('name', 'Cliente Removeu Referência')->firstOrFail();

        $this->assertDatabaseMissing('commercial_references', ['contact_id' => $contact->id, 'name' => 'Vai Sair']);
        $this->assertDatabaseHas('commercial_references', ['contact_id' => $contact->id, 'name' => 'Vai Ficar']);
    }

    public function test_editing_a_contact_loads_its_existing_reference_rows(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $contact = Contact::factory()->create(['company_id' => $company->id, 'name' => 'Cliente Existente']);
        $contact->commercialReferences()->create(['company_id' => $company->id, 'name' => 'Ref Existente', 'phone' => '4130000000']);
        $this->actingAs($user);

        Livewire::test(Form::class, ['contact' => $contact])
            ->assertSet('commercialReferenceRows.0.name', 'Ref Existente')
            ->assertSet('commercialReferenceRows.0.phone', '4130000000');
    }

    public function test_deleting_a_contact_removes_its_references_too(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $contact->commercialReferences()->create(['company_id' => $company->id, 'name' => 'Ref', 'phone' => '1']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $contact->id);

        $this->assertDatabaseMissing('commercial_references', ['contact_id' => $contact->id]);
    }

    private function fakeBrasilApiResponse(): array
    {
        return [
            'cnpj' => '11222333000181',
            'razao_social' => 'Empresa Exemplo LTDA',
            'nome_fantasia' => 'Exemplo',
            'descricao_situacao_cadastral' => 'ATIVA',
            'data_inicio_atividade' => '2010-01-01',
            'cnae_fiscal_descricao' => 'Comércio varejista',
            'logradouro' => 'Rua das Flores',
            'numero' => '123',
            'complemento' => 'Sala 4',
            'bairro' => 'Centro',
            'municipio' => 'Curitiba',
            'uf' => 'PR',
            'cep' => '80000000',
            'ddd_telefone_1' => '4130000000',
            'email' => 'contato@exemplo.com',
            'capital_social' => 100000,
            'porte' => 'DEMAIS',
        ];
    }

    public function test_busca_basica_button_only_appears_for_cnpj_document(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('document', '123.456.789-00')
            ->assertDontSee('Busca Básica');

        Livewire::test(Form::class)
            ->set('document', '11.222.333/0001-81')
            ->assertSee('Busca Básica');
    }

    public function test_document_type_defaults_to_individual_and_is_saved_explicitly(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->assertSet('document_type', 'individual')
            ->set('name', 'Pessoa Física')
            ->call('save');

        $this->assertDatabaseHas('contacts', [
            'name' => 'Pessoa Física',
            'document_type' => 'individual',
        ]);
    }

    public function test_document_type_is_inferred_from_document_until_manually_chosen(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('document', '11.222.333/0001-81')
            ->assertSet('document_type', 'company')
            ->set('document_type', 'individual') // usuário corrige manualmente
            ->set('document', '') // limpar o documento não deve mais sobrescrever
            ->assertSet('document_type', 'individual');
    }

    public function test_editing_a_contact_keeps_its_saved_document_type(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'document' => '123.456.789-00',
            'document_type' => 'company', // valor explícito, mesmo não parecendo CNPJ
        ]);
        $this->actingAs($user);

        Livewire::test(Form::class, ['contact' => $contact])
            ->assertSet('document_type', 'company')
            ->assertSee('Busca Básica');
    }

    public function test_department_contacts_section_only_appears_for_a_legal_entity(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('tab', 'references')
            ->set('document_type', 'individual')
            ->assertDontSee('Contatos de departamento');

        Livewire::test(Form::class)
            ->set('tab', 'references')
            ->set('document_type', 'company')
            ->assertSee('Contatos de departamento');
    }

    public function test_full_access_user_can_add_department_contacts(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Empresa Grande LTDA')
            ->set('document_type', 'company')
            ->call('addDepartmentContactRow')
            ->set('departmentContactRows.0.name', 'Ana Compras')
            ->set('departmentContactRows.0.role', 'Comprador')
            ->set('departmentContactRows.0.extension', '1234')
            ->set('departmentContactRows.0.email', 'ana@empresagrande.com')
            ->call('save');

        $contact = Contact::query()->where('name', 'Empresa Grande LTDA')->firstOrFail();

        $this->assertDatabaseHas('contact_department_contacts', [
            'contact_id' => $contact->id,
            'name' => 'Ana Compras',
            'role' => 'Comprador',
            'extension' => '1234',
            'email' => 'ana@empresagrande.com',
        ]);
    }

    public function test_editing_a_contact_loads_its_existing_department_contacts(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $contact = Contact::factory()->create(['company_id' => $company->id, 'document_type' => 'company']);
        $contact->departmentContacts()->create([
            'company_id' => $company->id,
            'name' => 'Bruno Financeiro',
            'role' => 'Analista',
        ]);
        $this->actingAs($user);

        Livewire::test(Form::class, ['contact' => $contact])
            ->assertSet('departmentContactRows.0.name', 'Bruno Financeiro')
            ->assertSet('departmentContactRows.0.role', 'Analista');
    }

    public function test_removing_a_department_contact_row_before_saving_does_not_persist_it(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('name', 'Sem Departamento')
            ->set('document_type', 'company')
            ->call('addDepartmentContactRow')
            ->set('departmentContactRows.0.name', 'Vai ser removido')
            ->call('removeDepartmentContactRow', 0)
            ->call('save');

        $contact = Contact::query()->where('name', 'Sem Departamento')->firstOrFail();

        $this->assertSame(0, $contact->departmentContacts()->count());
    }

    public function test_buscar_cnpj_fills_fields_from_the_api_response(): void
    {
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response($this->fakeBrasilApiResponse(), 200),
        ]);

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('document', '11.222.333/0001-81')
            ->call('buscarCnpj')
            ->assertSet('name', 'Empresa Exemplo LTDA')
            ->assertSet('city', 'Curitiba')
            ->assertSet('state', 'PR')
            ->assertSet('postal_code', '80000000')
            ->assertSet('district', 'Centro');
    }

    public function test_buscar_cnpj_then_save_attaches_a_pdf_document(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response($this->fakeBrasilApiResponse(), 200),
        ]);

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('document', '11.222.333/0001-81')
            ->call('buscarCnpj')
            ->call('save');

        $contact = Contact::query()->where('name', 'Empresa Exemplo LTDA')->firstOrFail();

        $this->assertDatabaseHas('contact_documents', [
            'contact_id' => $contact->id,
            'category' => 'consulta_cnpj',
            'mime_type' => 'application/pdf',
        ]);

        $document = $contact->documents()->first();
        Storage::disk('local')->assertExists($document->stored_path);
    }

    public function test_busca_avancada_link_only_appears_when_a_credential_has_a_url(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'credentials',
            'level' => 'read',
        ]);
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->assertDontSee('Busca Avançada');

        Credential::factory()->create([
            'company_id' => $company->id,
            'title' => 'SPC Brasil',
            'url' => 'https://www.spcbrasil.org.br/login',
        ]);

        Livewire::test(Form::class)
            ->assertSee('Busca Avançada — SPC Brasil');
    }
}
