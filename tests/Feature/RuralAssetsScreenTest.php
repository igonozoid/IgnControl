<?php

namespace Tests\Feature;

use App\Livewire\RuralAssets\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RuralAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuralAssetsScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'rural',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_full_access_user_can_create_and_delete(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Trator John Deere')
            ->set('asset_type', 'machinery')
            ->call('save');

        $this->assertDatabaseHas('rural_assets', [
            'company_id' => $company->id,
            'name' => 'Trator John Deere',
        ]);

        $asset = RuralAsset::query()->where('name', 'Trator John Deere')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $asset->id);

        $this->assertDatabaseMissing('rural_assets', ['id' => $asset->id]);
    }
}
