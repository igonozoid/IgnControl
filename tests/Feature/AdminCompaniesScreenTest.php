<?php

namespace Tests\Feature;

use App\Livewire\Admin\Companies;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCompaniesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id, ['role' => 'owner']);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'admin',
            'level' => 'full',
        ]);

        return $user;
    }

    public function test_user_without_admin_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id);

        $this->actingAs($user);

        Livewire::test(Companies::class)->assertForbidden();
    }

    public function test_admin_can_edit_the_active_company(): void
    {
        $company = Company::factory()->create(['name' => 'Antigo Nome']);
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('edit', $company->id)
            ->set('name', 'Novo Nome')
            ->set('legal_name', 'Novo Nome Ltda')
            ->set('tax_id', '12.345.678/0001-99')
            ->set('base_currency_code', 'BRL')
            ->set('is_active', true)
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertSame('Novo Nome', $company->fresh()->name);
        $this->assertSame('Novo Nome Ltda', $company->fresh()->legal_name);
    }

    public function test_admin_can_create_a_new_company_and_it_becomes_the_active_one(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('create')
            ->set('name', 'Filial Nova')
            ->set('base_currency_code', 'BRL')
            ->call('save')
            ->assertSet('showForm', false);

        $newCompany = Company::query()->where('name', 'Filial Nova')->firstOrFail();

        $this->assertSame($newCompany->id, $user->fresh()->current_company_id);
        $this->assertTrue($newCompany->users()->where('users.id', $user->id)->exists());
        $this->assertSame(
            count(Permission::MODULES),
            Permission::query()->where('company_id', $newCompany->id)->where('user_id', $user->id)->where('level', 'full')->count()
        );
        // Módulos verticais nascem todos desmarcados — quem cria escolhe.
        $this->assertSame([], $newCompany->enabled_modules);
    }

    public function test_admin_can_toggle_optional_modules(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('create')
            ->set('name', 'Fazenda Modelo')
            ->set('base_currency_code', 'BRL')
            ->set('optionalModules.rural', true)
            ->set('optionalModules.hr', true)
            ->call('save')
            ->assertSet('showForm', false);

        $newCompany = Company::query()->where('name', 'Fazenda Modelo')->firstOrFail();

        $this->assertEqualsCanonicalizing(['rural', 'hr'], $newCompany->enabled_modules);
    }

    private function fakeBrasilApiResponse(): array
    {
        return [
            'cnpj' => '11222333000181',
            'razao_social' => 'Empresa Exemplo LTDA',
            'nome_fantasia' => 'Exemplo',
            'logradouro' => 'Rua das Flores',
            'numero' => '123',
            'complemento' => 'Sala 4',
            'bairro' => 'Centro',
            'municipio' => 'Curitiba',
            'uf' => 'PR',
            'cep' => '80000000',
            'ddd_telefone_1' => '4130000000',
            'email' => 'contato@exemplo.com',
        ];
    }

    public function test_buscar_cnpj_fills_fields_from_the_api_response(): void
    {
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response($this->fakeBrasilApiResponse(), 200),
        ]);

        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('create')
            ->set('tax_id', '11.222.333/0001-81')
            ->call('buscarCnpj')
            ->assertSet('legal_name', 'Empresa Exemplo LTDA')
            ->assertSet('city', 'Curitiba')
            ->assertSet('state', 'PR')
            ->assertSet('postal_code', '80000000')
            ->assertSet('district', 'Centro');
    }

    public function test_buscar_cnpj_is_not_offered_for_pessoa_fisica(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('create')
            ->set('person_type', 'PF')
            ->assertDontSee('Busca Básica');

        Livewire::test(Companies::class)
            ->call('create')
            ->set('person_type', 'PJ')
            ->assertSee('Busca Básica');
    }

    public function test_admin_can_upload_and_remove_a_company_logo(): void
    {
        Storage::fake('local');

        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$', 'decimals' => 2, 'is_active' => true]);
        $this->actingAs($user);

        Livewire::test(Companies::class)
            ->call('edit', $company->id)
            ->set('base_currency_code', 'BRL')
            ->set('logo', UploadedFile::fake()->create('logo.png', 10, 'image/png'))
            ->call('save');

        $this->assertNotNull($company->fresh()->logo_path);
        Storage::disk('local')->assertExists($company->fresh()->logo_path);

        Livewire::test(Companies::class)
            ->call('edit', $company->id)
            ->call('removeLogoNow')
            ->call('save');

        $this->assertNull($company->fresh()->logo_path);
    }

    public function test_admin_cannot_edit_a_company_they_do_not_belong_to(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $otherCompany = Company::factory()->create();

        $this->actingAs($user);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Companies::class)->call('edit', $otherCompany->id);
    }
}
