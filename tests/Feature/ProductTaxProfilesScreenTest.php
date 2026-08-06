<?php

namespace Tests\Feature;

use App\Livewire\ProductTaxProfiles\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\ProductTaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTaxProfilesScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'sales',
            'level' => $level,
        ]);

        return $user;
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
            ->set('name', 'ICMS Padrão')
            ->set('tax_mode', 'rate')
            ->set('default_rate_percent', '18')
            ->call('save');

        $this->assertDatabaseHas('product_tax_profiles', [
            'company_id' => $company->id,
            'name' => 'ICMS Padrão',
        ]);

        $profile = ProductTaxProfile::query()->where('name', 'ICMS Padrão')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $profile->id);

        $this->assertDatabaseMissing('product_tax_profiles', ['id' => $profile->id]);
    }
}
