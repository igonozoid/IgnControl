<?php

namespace Tests\Feature;

use App\Livewire\Tasks\Index;
use App\Models\Company;
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
}
