<?php

namespace Tests\Feature;

use App\Livewire\CropSeasons\Index;
use App\Models\Company;
use App\Models\CropSeason;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RuralField;
use App\Models\RuralProperty;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CropSeasonsScreenTest extends TestCase
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

    public function test_full_access_user_can_create_a_season(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('field_id', (string) $field->id)
            ->set('crop_name', 'Soja')
            ->set('season_label', 'Safra 2025/2026')
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('crop_seasons', [
            'company_id' => $company->id,
            'crop_name' => 'Soja',
            'season_label' => 'Safra 2025/2026',
            'status' => 'planned',
        ]);
    }

    public function test_marking_as_harvested_generates_stock_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $season = CropSeason::factory()->create([
            'company_id' => $company->id,
            'field_id' => $field->id,
            'harvested_product_id' => $product->id,
            'status' => 'growing',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openHarvestForm', $season->id)
            ->set('actual_harvest_date', now()->toDateString())
            ->set('actual_yield', '900')
            ->call('markHarvested')
            ->assertSet('showHarvestForm', false);

        $season->refresh();
        $this->assertSame('harvested', $season->status);
        $this->assertSame(900.0, app(StockService::class)->available($product->id));
    }

    public function test_cannot_delete_a_harvested_season(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $field = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $season = CropSeason::factory()->create(['company_id' => $company->id, 'field_id' => $field->id, 'status' => 'harvested']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $season->id)->assertForbidden();

        $this->assertDatabaseHas('crop_seasons', ['id' => $season->id]);
    }
}
