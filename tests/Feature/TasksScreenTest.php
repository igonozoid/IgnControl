<?php

namespace Tests\Feature;

use App\Livewire\Tasks\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Permission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksScreenTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLevel(Company $company, string $level): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);

        Permission::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'module' => 'agenda',
            'level' => $level,
        ]);

        return $user;
    }

    public function test_user_without_agenda_access_is_blocked(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'none');
        $this->actingAs($user);

        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_list_only_shows_tasks_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userWithLevel($companyA, 'read');

        Task::factory()->create(['company_id' => $companyA->id, 'title' => 'Tarefa Empresa A']);
        Task::factory()->create(['company_id' => $companyB->id, 'title' => 'Tarefa Empresa B']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Tarefa Empresa A')
            ->assertDontSee('Tarefa Empresa B');
    }

    public function test_read_only_user_cannot_create(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_full_access_user_can_create_a_task(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('title', 'Ligar pro fornecedor')
            ->set('due_date', now()->addDays(2)->toDateString())
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'company_id' => $company->id,
            'title' => 'Ligar pro fornecedor',
            'status' => 'pending',
        ]);
    }

    public function test_toggle_done_marks_and_unmarks_a_task(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create(['company_id' => $company->id, 'status' => 'pending']);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('toggleDone', $task->id);
        $task->refresh();
        $this->assertSame('done', $task->status);
        $this->assertNotNull($task->completed_at);

        Livewire::test(Index::class)->call('toggleDone', $task->id);
        $this->assertSame('pending', $task->refresh()->status);
        $this->assertNull($task->completed_at);
    }

    public function test_completing_a_weekly_recurring_task_generates_the_next_occurrence(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create([
            'company_id' => $company->id,
            'title' => 'Regar as plantas',
            'status' => 'pending',
            'due_date' => '2026-03-03', // terça
            'recurrence_type' => 'weekly',
            'recurrence_weekday' => 2,
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('toggleDone', $task->id);

        $this->assertSame('done', $task->refresh()->status);

        $next = Task::query()->where('title', 'Regar as plantas')->where('status', 'pending')->first();
        $this->assertNotNull($next);
        $this->assertSame('2026-03-10', $next->due_date->toDateString());
        $this->assertSame('weekly', $next->recurrence_type);
    }

    public function test_completing_a_monthly_recurring_task_respects_the_anchor_day(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create([
            'company_id' => $company->id,
            'title' => 'Pagar condomínio',
            'status' => 'pending',
            'due_date' => '2026-01-31',
            'recurrence_type' => 'monthly',
            'recurrence_day_of_month' => 31,
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('toggleDone', $task->id);

        // Fevereiro não tem dia 31 — cai no último dia do mês (28, 2026
        // não é bissexto), igual ao legado.
        $next = Task::query()->where('title', 'Pagar condomínio')->where('status', 'pending')->first();
        $this->assertSame('2026-02-28', $next->due_date->toDateString());
    }

    public function test_custom_recurrence_does_not_generate_a_next_occurrence(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create([
            'company_id' => $company->id,
            'title' => 'Ir ao cartório',
            'status' => 'pending',
            'due_date' => '2026-03-03',
            'recurrence_type' => 'custom',
            'recurrence_note' => 'Quando precisar',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('toggleDone', $task->id);

        $this->assertSame(1, Task::query()->where('title', 'Ir ao cartório')->count());
    }

    public function test_reopening_a_recurring_task_is_blocked_while_the_next_occurrence_is_open(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create([
            'company_id' => $company->id,
            'title' => 'Backup semanal',
            'status' => 'pending',
            'due_date' => '2026-03-03',
            'recurrence_type' => 'weekly',
        ]);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('toggleDone', $task->id); // conclui, gera a próxima
        $this->assertSame('done', $task->refresh()->status);

        Livewire::test(Index::class)->call('toggleDone', $task->id) // tenta reabrir
            ->assertSee('já gerou a próxima ocorrência');

        $this->assertSame('done', $task->refresh()->status);
    }

    public function test_full_access_user_can_delete_a_task(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'full');
        $task = Task::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        Livewire::test(Index::class)->call('delete', $task->id);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_pending_status_filter_hides_done_tasks(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Task::factory()->create(['company_id' => $company->id, 'title' => 'Pendente', 'status' => 'pending']);
        Task::factory()->create(['company_id' => $company->id, 'title' => 'Concluida', 'status' => 'done']);
        $this->actingAs($user);

        Livewire::test(Index::class)->set('status', 'pending')
            ->assertSee('Pendente')
            ->assertDontSee('Concluida');
    }

    public function test_overdue_task_is_flagged(): void
    {
        $task = new Task(['status' => 'pending', 'due_date' => now()->subDay()->toDateString()]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_month_view_shows_a_task_due_that_month(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Task::factory()->create(['company_id' => $company->id, 'title' => 'Reunião mensal', 'due_date' => '2026-08-15']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('view', 'month')
            ->set('anchorDate', '2026-08-01')
            ->assertSee('Reunião mensal');
    }

    public function test_month_view_does_not_show_a_task_from_another_month(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Task::factory()->create(['company_id' => $company->id, 'title' => 'Tarefa de setembro', 'due_date' => '2026-09-20']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('view', 'month')
            ->set('anchorDate', '2026-08-01')
            ->assertDontSee('Tarefa de setembro');
    }

    public function test_next_and_previous_period_move_the_anchor_date_by_view(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('view', 'month')
            ->set('anchorDate', '2026-08-15')
            ->call('nextPeriod')
            ->assertSet('anchorDate', '2026-09-15')
            ->call('previousPeriod')
            ->call('previousPeriod')
            ->assertSet('anchorDate', '2026-07-15');

        Livewire::test(Index::class)
            ->set('view', 'day')
            ->set('anchorDate', '2026-08-15')
            ->call('nextPeriod')
            ->assertSet('anchorDate', '2026-08-16');
    }

    public function test_go_to_day_switches_to_day_view_on_that_date(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('view', 'month')
            ->call('goToDate', '2026-08-20')
            ->assertSet('view', 'day')
            ->assertSet('anchorDate', '2026-08-20');
    }

    public function test_contact_birthday_appears_in_month_view(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Contact::factory()->create(['company_id' => $company->id, 'name' => 'Aniversariante', 'birth_date' => '1985-08-10']);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('view', 'month')
            ->set('anchorDate', '2026-08-01')
            ->assertSee('Aniversariante');
    }

    public function test_contact_birthday_appears_in_list_view_upcoming_widget(): void
    {
        $this->travelTo('2026-08-01');

        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Contact::factory()->create(['company_id' => $company->id, 'name' => 'Cliente Aniversariante', 'birth_date' => '1990-08-10']);
        $this->actingAs($user);

        Livewire::test(Index::class)->assertSee('Cliente Aniversariante');

        $this->travelBack();
    }

    public function test_birthday_does_not_appear_outside_the_visible_range(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWithLevel($company, 'read');
        Contact::factory()->create(['company_id' => $company->id, 'name' => 'Aniversariante de Dezembro', 'birth_date' => '1985-12-25']);
        $this->actingAs($user);

        // Verifica direto nos dados da grade (não no HTML), porque o nome do
        // contato legitimamente aparece em outro lugar da tela — no dropdown
        // "Vincular a um contato" do formulário, que lista todos os
        // contatos, não só os aniversariantes do mês visível.
        $cells = Livewire::test(Index::class)
            ->set('view', 'month')
            ->set('anchorDate', '2026-08-01')
            ->viewData('cells');

        $hasBirthday = collect($cells)->contains(fn ($cell) => $cell['birthdays']->isNotEmpty());

        $this->assertFalse($hasBirthday);
    }
}
