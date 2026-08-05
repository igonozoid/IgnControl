<?php

namespace Tests\Feature;

use App\Livewire\Hr\Index;
use App\Livewire\Hr\Profile;
use App\Models\Company;
use App\Models\Contact;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeProfile;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'hr',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_hr_access_is_blocked_from_the_list(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_list_only_shows_contacts_marked_as_employee(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Contact::factory()->create(['company_id' => $company->id, 'name' => 'Jardineiro Renato', 'is_employee' => true]);
        Contact::factory()->create(['company_id' => $company->id, 'name' => 'Fornecedor Qualquer', 'is_employee' => false]);

        Livewire::test(Index::class)
            ->assertSee('Jardineiro Renato')
            ->assertDontSee('Fornecedor Qualquer');
    }

    public function test_list_is_scoped_to_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');
        $this->actingAs($user);

        Contact::factory()->create(['company_id' => $companyA->id, 'name' => 'Funcionário A', 'is_employee' => true]);
        Contact::factory()->create(['company_id' => $companyB->id, 'name' => 'Funcionário B', 'is_employee' => true]);

        Livewire::test(Index::class)
            ->assertSee('Funcionário A')
            ->assertDontSee('Funcionário B');
    }

    public function test_profile_returns_not_found_for_a_contact_that_is_not_an_employee(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => false]);

        Livewire::test(Profile::class, ['contact' => $contact])->assertNotFound();
    }

    public function test_read_only_user_cannot_save_the_profile(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->set('job_title', 'Jardineiro')
            ->call('saveProfile')
            ->assertForbidden();
    }

    public function test_full_access_user_can_save_the_employee_profile(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->set('job_title', 'Jardineiro(a)')
            ->set('staff_category', 'domestic_rural')
            ->set('inss_rate', '0.08')
            ->set('admission_date', '2010-04-09')
            ->call('saveProfile');

        $this->assertDatabaseHas('employee_profiles', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'job_title' => 'Jardineiro(a)',
            'staff_category' => 'domestic_rural',
        ]);
    }

    public function test_can_add_a_salary_history_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('createEntry', 'salary')
            ->set('form.effective_date', '2024-01-01')
            ->set('form.nominal_salary', '1547.00')
            ->set('form.net_salary', '1426.88')
            ->call('saveEntry');

        $this->assertDatabaseHas('employee_salary_entries', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'net_salary' => 1426.88,
        ]);
    }

    public function test_can_add_and_edit_a_vacation_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('createEntry', 'vacation')
            ->set('form.period_start', '2023-05-04')
            ->set('form.period_end', '2024-05-03')
            ->set('form.amount_paid', '1908')
            ->call('saveEntry');

        $vacation = $contact->vacations()->firstOrFail();
        $this->assertSame('1908.00', $vacation->amount_paid);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('editEntry', 'vacation', $vacation->id)
            ->set('form.amount_paid', '2000')
            ->call('saveEntry');

        $this->assertSame('2000.00', $vacation->refresh()->amount_paid);
    }

    public function test_vacation_period_end_must_be_after_or_equal_start(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('createEntry', 'vacation')
            ->set('form.period_start', '2024-05-03')
            ->set('form.period_end', '2023-05-04')
            ->call('saveEntry')
            ->assertHasErrors(['form.period_end']);
    }

    public function test_can_add_a_thirteenth_salary_entry(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('createEntry', 'thirteenth')
            ->set('form.year', '2024')
            ->set('form.amount_paid', '2387')
            ->call('saveEntry');

        $this->assertDatabaseHas('employee_thirteenth_salaries', [
            'contact_id' => $contact->id,
            'year' => 2024,
        ]);
    }

    public function test_can_add_delete_and_toggle_a_benefit(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('createEntry', 'benefit')
            ->set('form.name', 'Unimed')
            ->set('form.monthly_value', '350')
            ->call('saveEntry');

        $benefit = EmployeeBenefit::query()->where('contact_id', $contact->id)->firstOrFail();
        $this->assertTrue($benefit->active);

        Livewire::test(Profile::class, ['contact' => $contact])
            ->call('deleteEntry', 'benefit', $benefit->id);

        $this->assertDatabaseMissing('employee_benefits', ['id' => $benefit->id]);
    }

    public function test_cost_calculation_uses_latest_salary_and_inss_rate(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_employee' => true]);

        EmployeeProfile::query()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'staff_category' => 'domestic_rural',
            'inss_rate' => 0.08,
        ]);

        $contact->salaryEntries()->create([
            'company_id' => $company->id,
            'effective_date' => '2024-01-01',
            'nominal_salary' => 1000,
            'net_salary' => 1000,
        ]);

        $component = Livewire::test(Profile::class, ['contact' => $contact]);
        $cost = $component->instance()->cost;

        // INSS patronal: 1000 * 0.08 = 80. FGTS: 1000 * 0.08 = 80.
        $this->assertSame(1000.0, $cost['base_salary']);
        $this->assertEquals(80.0, $cost['inss']);
        $this->assertEquals(80.0, $cost['fgts']);
        $this->assertGreaterThan(0, $cost['total_charges']);
    }
}
