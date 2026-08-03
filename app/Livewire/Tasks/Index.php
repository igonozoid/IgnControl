<?php

namespace App\Livewire\Tasks;

use App\Models\Contact;
use App\Models\FinancialEntry;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Agenda/tarefas: pendências manuais (ligar pro cliente, conferir um
 * lançamento, etc.), com data e vínculo opcional a um contato e/ou
 * lançamento financeiro. Módulo próprio ('agenda') — quem só cuida do
 * financeiro não precisa necessariamente ver a agenda de outra pessoa,
 * e vice-versa.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'pending'; // pending | done | all
    #[Url]
    public string $search = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('nullable|date')]
    public string $due_date = '';

    #[Validate('nullable|exists:contacts,id')]
    public ?int $contact_id = null;

    #[Validate('nullable|exists:financial_entries,id')]
    public ?int $financial_entry_id = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('agenda', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('agenda', 'full');
    }

    private function resetForm(): void
    {
        $this->reset(['title', 'description', 'due_date', 'contact_id', 'financial_entry_id', 'editingId']);
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $task = Task::query()->findOrFail($id);

        $this->editingId = $task->id;
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->due_date = $task->due_date?->toDateString() ?? '';
        $this->contact_id = $task->contact_id;
        $this->financial_entry_id = $task->financial_entry_id;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['due_date'] = $data['due_date'] ?: null;

        if ($this->editingId) {
            Task::query()->findOrFail($this->editingId)->update($data);
        } else {
            Task::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleDone(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $task = Task::query()->findOrFail($id);
        $task->update($task->status === 'done'
            ? ['status' => 'pending', 'completed_at' => null]
            : ['status' => 'done', 'completed_at' => now()]);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Task::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $tasks = Task::query()
            ->with(['contact', 'financialEntry'])
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderByRaw('due_date is null, due_date')
            ->paginate(20);

        return view('livewire.tasks.index', [
            'tasks' => $tasks,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'financialEntries' => FinancialEntry::query()->where('status', 'pending')->orderBy('due_date')->limit(200)->get(),
        ]);
    }
}
