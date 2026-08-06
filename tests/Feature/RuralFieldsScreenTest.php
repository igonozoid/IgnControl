<?php

namespace Tests\Feature;

use App\Livewire\RuralFields\Index;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RuralField;
use App\Models\RuralProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuralFieldsScreenTest extends TestCase
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
        $property = RuralProperty::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('property_id', (string) $property->id)
            ->set('name', 'Talhão 1')
            ->call('save');

        $this->assertDatabaseHas('rural_fields', [
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Talhão 1',
        ]);

        $field = RuralField::query()->where('name', 'Talhão 1')->firstOrFail();

        Livewire::test(Index::class)->call('delete', $field->id);

        $this->assertDatabaseMissing('rural_fields', ['id' => $field->id]);
    }

    public function test_filter_by_property_only_shows_matching_fields(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $propertyA = RuralProperty::factory()->create(['company_id' => $company->id]);
        $propertyB = RuralProperty::factory()->create(['company_id' => $company->id]);
        $fieldA = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $propertyA->id]);
        $fieldB = RuralField::factory()->create(['company_id' => $company->id, 'property_id' => $propertyB->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterProperty', (string) $propertyA->id)
            ->assertSee('rural-field-'.$fieldA->id, false)
            ->assertDontSee('rural-field-'.$fieldB->id, false);
    }
}
