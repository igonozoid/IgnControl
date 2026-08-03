<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_contacts_from_their_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['current_company_id' => $companyA->id]);

        Contact::factory()->count(3)->create(['company_id' => $companyA->id]);
        Contact::factory()->count(5)->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);

        $this->assertSame(3, Contact::query()->count());
        $this->assertTrue(Contact::query()->pluck('company_id')->every(fn ($id) => $id === $companyA->id));
    }

    public function test_new_contact_is_stamped_with_the_active_company_automatically(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->id]);

        $this->actingAs($user);

        $contact = Contact::factory()->make(['company_id' => null]);
        $contact->save();

        $this->assertSame($company->id, $contact->fresh()->company_id);
    }
}
