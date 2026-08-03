<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\CostCenters\Index as CostCentersIndex;
use App\Livewire\FinancialEntries\Index as FinancialEntriesIndex;
use App\Models\Category;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickCreateAndReviewTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $module, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => $module,
            'level' => $level,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    }

    public function test_quick_creating_a_contact_from_the_entry_form_marks_it_for_review_and_selects_it(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        FinancialAccount::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $component = Livewire::test(FinancialEntriesIndex::class)->set('tab', 'expense')
            ->call('create')
            ->set('showQuickContact', true)
            ->set('quickContactName', 'Fornecedor Novo')
            ->call('quickCreateContact');

        $contact = Contact::query()->where('name', 'Fornecedor Novo')->firstOrFail();

        $this->assertTrue($contact->needs_review);
        $this->assertTrue($contact->is_supplier);
        $component->assertSet('contact_id', $contact->id);
        $component->assertSet('showQuickContact', false);
    }

    public function test_quick_creating_a_category_uses_the_active_tab_as_the_type(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $this->actingAs($user);

        Livewire::test(FinancialEntriesIndex::class)->set('tab', 'income')
            ->call('create')
            ->set('quickCategoryName', 'Consultoria')
            ->call('quickCreateCategory');

        $category = Category::query()->where('name', 'Consultoria')->firstOrFail();
        $this->assertSame('income', $category->type);
        $this->assertTrue($category->needs_review);
    }

    public function test_quick_creating_a_cost_center_marks_it_for_review(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $this->actingAs($user);

        Livewire::test(FinancialEntriesIndex::class)->set('tab', 'expense')
            ->call('create')
            ->set('quickCostCenterName', 'Obra Nova')
            ->call('quickCreateCostCenter');

        $costCenter = CostCenter::query()->where('name', 'Obra Nova')->firstOrFail();
        $this->assertTrue($costCenter->needs_review);
    }

    public function test_editing_a_contact_pending_review_clears_the_flag(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'contacts', 'full');
        $contact = Contact::factory()->create(['company_id' => $company->id, 'needs_review' => true]);
        $this->actingAs($user);

        Livewire::test(ContactsIndex::class)
            ->call('edit', $contact->id)
            ->set('email', 'contato@exemplo.com')
            ->call('save');

        $this->assertFalse($contact->refresh()->needs_review);
    }

    public function test_mark_reviewed_clears_the_flag_without_editing(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'full');
        $costCenter = CostCenter::factory()->create(['company_id' => $company->id, 'needs_review' => true]);
        $this->actingAs($user);

        Livewire::test(CostCentersIndex::class)->call('markReviewed', $costCenter->id);

        $this->assertFalse($costCenter->refresh()->needs_review);
    }

    public function test_only_needs_review_filter_hides_reviewed_categories(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'financial', 'read');
        $pending = Category::factory()->create(['company_id' => $company->id, 'name' => 'Pendente', 'needs_review' => true]);
        $reviewed = Category::factory()->create(['company_id' => $company->id, 'name' => 'Revisada', 'needs_review' => false]);
        $this->actingAs($user);

        // "Revisada" ainda aparece na página mesmo filtrando — é o
        // seletor de categoria-pai do formulário, que lista todas de
        // propósito. O que importa é a LINHA da tabela de cada uma.
        Livewire::test(CategoriesIndex::class)->set('onlyNeedsReview', true)
            ->assertSee('wire:key="category-'.$pending->id.'"', false)
            ->assertDontSee('wire:key="category-'.$reviewed->id.'"', false);
    }
}
