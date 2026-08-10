<?php

namespace App\Livewire\Tasks;

use App\Models\Contact;
use App\Models\FinancialEntry;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Livewire\Concerns\HasPerPageSelector;
use Livewire\WithPagination;

/**
 * Agenda/tarefas: pendências manuais (ligar pro cliente, conferir um
 * lançamento, etc.), com data e vínculo opcional a um contato e/ou
 * lançamento financeiro. Módulo próprio ('agenda') — quem só cuida do
 * financeiro não precisa necessariamente ver a agenda de outra pessoa,
 * e vice-versa.
 *
 * Quatro formas de ver a mesma agenda (igual Google Agenda): lista, mês,
 * semana e dia. Todas respeitam o mesmo filtro de situação. Aniversários
 * (campo Contact::birth_date) entram automaticamente como lembretes
 * anuais, calculados na hora — não são um registro salvo no banco.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use HasPerPageSelector, WithPagination;

    #[Url]
    public string $view = 'list'; // list | month | week | day

    #[Url]
    public string $anchorDate = '';

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

    // Recorrência — mesmas opções do legado (Task::RECURRENCE_TYPES).
    // weekday/day_of_month são só âncora informativa; quem calcula a
    // próxima data de verdade é Task::nextRecurrenceDate().
    #[Validate('required|in:none,daily,weekly,fortnightly,monthly,bimonthly,quarterly,semiannual,yearly,biennial,custom')]
    public string $recurrence_type = 'none';

    #[Validate('nullable|integer|min:0|max:6')]
    public ?int $recurrence_weekday = null;

    #[Validate('nullable|integer|min:1|max:31')]
    public ?int $recurrence_day_of_month = null;

    #[Validate('nullable|string|max:255')]
    public string $recurrence_note = '';

    // Mensagem exibida quando tenta reabrir uma tarefa recorrente que já
    // gerou a próxima ocorrência (evita duas ocorrências abertas juntas).
    public ?string $recurrenceError = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('agenda', 'read'), 403);
        $this->anchorDate = $this->anchorDate ?: now()->toDateString();
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('agenda', 'full');
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['list', 'month', 'week', 'day'], true) ? $view : 'list';
    }

    public function goToday(): void
    {
        $this->anchorDate = now()->toDateString();
    }

    public function goToDate(string $date): void
    {
        $this->anchorDate = $date;
        $this->view = 'day';
    }

    public function previousPeriod(): void
    {
        $anchor = Carbon::parse($this->anchorDate);
        $this->anchorDate = match ($this->view) {
            'month' => $anchor->subMonthNoOverflow()->toDateString(),
            'week' => $anchor->subWeek()->toDateString(),
            'day' => $anchor->subDay()->toDateString(),
            default => $this->anchorDate,
        };
    }

    public function nextPeriod(): void
    {
        $anchor = Carbon::parse($this->anchorDate);
        $this->anchorDate = match ($this->view) {
            'month' => $anchor->addMonthNoOverflow()->toDateString(),
            'week' => $anchor->addWeek()->toDateString(),
            'day' => $anchor->addDay()->toDateString(),
            default => $this->anchorDate,
        };
    }

    private function resetForm(): void
    {
        $this->reset([
            'title', 'description', 'due_date', 'contact_id', 'financial_entry_id', 'editingId',
            'recurrence_weekday', 'recurrence_day_of_month', 'recurrence_note',
        ]);
        $this->recurrence_type = 'none';
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        // Se abrir "nova tarefa" a partir de uma tela de calendário, já
        // sugere a data do dia que estava sendo visualizado.
        if ($this->view !== 'list') {
            $this->due_date = $this->anchorDate;
        }
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
        $this->recurrence_type = $task->recurrence_type ?? 'none';
        $this->recurrence_weekday = $task->recurrence_weekday;
        $this->recurrence_day_of_month = $task->recurrence_day_of_month;
        $this->recurrence_note = (string) $task->recurrence_note;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['due_date'] = $data['due_date'] ?: null;
        $data['recurrence_note'] = $data['recurrence_note'] ?: null;

        if (! in_array($this->recurrence_type, ['weekly', 'fortnightly'], true)) {
            $data['recurrence_weekday'] = null;
        }
        if (! array_key_exists($this->recurrence_type, Task::RECURRENCE_TYPES) || in_array($this->recurrence_type, ['none', 'daily', 'weekly', 'fortnightly', 'custom'], true)) {
            $data['recurrence_day_of_month'] = null;
        }

        if ($this->editingId) {
            Task::query()->findOrFail($this->editingId)->update($data);
        } else {
            Task::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Concluir gera a próxima ocorrência sozinho quando a tarefa é
     * recorrente (igual ao legado — não é uma lista pré-gerada). Reabrir
     * uma tarefa recorrente cuja próxima ocorrência já foi gerada e ainda
     * está aberta é bloqueado, pra não ficar com duas ocorrências abertas
     * da mesma recorrência ao mesmo tempo.
     */
    public function toggleDone(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $this->recurrenceError = null;
        $task = Task::query()->findOrFail($id);

        if ($task->status === 'done') {
            if ($task->recurrence_type !== 'none' && $this->hasOpenNextOccurrence($task)) {
                $this->recurrenceError = 'Essa tarefa recorrente já gerou a próxima ocorrência (ainda aberta). Reabra a nova ocorrência, ou edite a recorrência antes de reabrir esta.';

                return;
            }

            $task->update(['status' => 'pending', 'completed_at' => null]);

            return;
        }

        $task->update(['status' => 'done', 'completed_at' => now()]);

        $nextDate = $task->nextRecurrenceDate();
        if ($nextDate) {
            Task::query()->create([
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $nextDate->toDateString(),
                'recurrence_type' => $task->recurrence_type,
                'recurrence_weekday' => $task->recurrence_weekday,
                'recurrence_day_of_month' => $task->recurrence_day_of_month,
                'recurrence_note' => $task->recurrence_note,
                'status' => 'pending',
                'contact_id' => $task->contact_id,
                'financial_entry_id' => $task->financial_entry_id,
            ]);
        }
    }

    private function hasOpenNextOccurrence(Task $task): bool
    {
        return Task::query()
            ->where('id', '!=', $task->id)
            ->where('status', '!=', 'done')
            ->where('title', $task->title)
            ->where('recurrence_type', $task->recurrence_type)
            ->where('due_date', '>', $task->due_date)
            ->exists();
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

    /**
     * Ocorrências anuais de uma data de contato (aniversário ou "data
     * importante") que caem dentro do intervalo [from, to]. Calculado na
     * hora, olhando o dia/mês do cadastro no(s) ano(s) que o intervalo
     * cobre — não existe uma linha no banco pra isso.
     */
    private function dateReminderOccurrences(string $dateColumn, string $from, string $to): Collection
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        return Contact::query()
            ->whereNotNull($dateColumn)
            ->get()
            ->flatMap(function (Contact $contact) use ($dateColumn, $from, $to) {
                $rows = collect();
                $baseDate = $contact->{$dateColumn};

                foreach (array_unique([$from->year, $to->year]) as $year) {
                    $month = $baseDate->month;
                    $day = min($baseDate->day, Carbon::create($year, $month, 1)->daysInMonth);
                    $occurrence = Carbon::create($year, $month, $day)->startOfDay();

                    if ($occurrence->betweenIncluded($from, $to)) {
                        $rows->push(['date' => $occurrence->toDateString(), 'contact' => $contact]);
                    }
                }

                return $rows;
            });
    }

    private function birthdayOccurrences(string $from, string $to): Collection
    {
        return $this->dateReminderOccurrences('birth_date', $from, $to);
    }

    /**
     * "Data importante" — segunda data-lembrete do contato, além do
     * aniversário (ex.: aniversário de fundação de uma empresa-cliente).
     */
    private function importantDateOccurrences(string $from, string $to): Collection
    {
        return $this->dateReminderOccurrences('important_date', $from, $to);
    }

    /**
     * Monta a grade de dias (mês/semana/dia) já com as tarefas e os
     * aniversários de cada dia agrupados.
     */
    private function buildCalendarCells(Carbon $gridStart, Carbon $gridEnd, ?Carbon $currentMonthAnchor): Collection
    {
        $tasksByDay = Task::query()
            ->with('contact')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            // date(due_date) normaliza a comparação: o Eloquent grava
            // colunas `date` com hora embutida ("Y-m-d 00:00:00"), o que
            // deixa whereBetween com string pura de data pouco confiável
            // no SQLite.
            ->whereBetween(DB::raw('date(due_date)'), [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn (Task $task) => $task->due_date->toDateString());

        $birthdaysByDay = $this->birthdayOccurrences($gridStart->toDateString(), $gridEnd->toDateString())
            ->groupBy('date');

        $importantDatesByDay = $this->importantDateOccurrences($gridStart->toDateString(), $gridEnd->toDateString())
            ->groupBy('date');

        $cells = collect();
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $key = $cursor->toDateString();

            $cells->push([
                'date' => $cursor->copy(),
                'inCurrentMonth' => ! $currentMonthAnchor || $cursor->month === $currentMonthAnchor->month,
                'isToday' => $cursor->isToday(),
                'tasks' => $tasksByDay->get($key, collect()),
                'birthdays' => $birthdaysByDay->get($key, collect()),
                'importantDates' => $importantDatesByDay->get($key, collect()),
            ]);

            $cursor->addDay();
        }

        return $cells;
    }

    public function render()
    {
        // Carbon::translatedFormat() usa o idioma configurado nele (não o
        // do Laravel) — sem isso, mês/dia da semana saem em inglês, já
        // que APP_LOCALE do projeto é 'en' (só afeta textos do Laravel
        // em si, tipo mensagens de validação).
        Carbon::setLocale('pt_BR');

        $data = [
            'view' => $this->view,
            'anchorDate' => $this->anchorDate,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'financialEntries' => FinancialEntry::query()->where('status', 'pending')->orderBy('due_date')->limit(200)->get(),
        ];

        if ($this->view === 'list') {
            $data['tasks'] = Task::query()
                ->with(['contact', 'financialEntry'])
                ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
                ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->orderByRaw('due_date is null, due_date')
                ->paginate($this->perPage);

            $data['upcomingBirthdays'] = $this->birthdayOccurrences(now()->toDateString(), now()->addDays(30)->toDateString())
                ->sortBy('date')
                ->values();

            $data['upcomingImportantDates'] = $this->importantDateOccurrences(now()->toDateString(), now()->addDays(30)->toDateString())
                ->sortBy('date')
                ->values();

            return view('livewire.tasks.index', $data);
        }

        $anchor = Carbon::parse($this->anchorDate);

        if ($this->view === 'month') {
            $monthStart = $anchor->copy()->startOfMonth();
            $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
            $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $data['periodLabel'] = $anchor->translatedFormat('F').' de '.$anchor->year;
            $data['cells'] = $this->buildCalendarCells($gridStart, $gridEnd, $monthStart);
            $data['weekdayLabels'] = $this->weekdayLabels();
        } elseif ($this->view === 'week') {
            $gridStart = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
            $gridEnd = $gridStart->copy()->addDays(6);
            $data['periodLabel'] = $gridStart->format('d/m').' – '.$gridEnd->format('d/m/Y');
            $data['cells'] = $this->buildCalendarCells($gridStart, $gridEnd, null);
            $data['weekdayLabels'] = $this->weekdayLabels();
        } else { // day
            $data['periodLabel'] = ucfirst($anchor->translatedFormat('l')).', '.$anchor->format('d/m/Y');
            $data['cells'] = $this->buildCalendarCells($anchor, $anchor, null);
        }

        return view('livewire.tasks.index', $data);
    }

    private function weekdayLabels(): array
    {
        return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    }
}
