<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="calendar" class="w-4 h-4" />Agenda / Tarefas</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Nova tarefa
            </button>
        @endif
    </div>

    {{-- Alternador de visão --}}
    <div class="border-b border-gray-200 dark:border-neutral-700 mb-3">
        <nav class="-mb-px flex space-x-1">
            @foreach (['list' => 'Lista', 'month' => 'Mês', 'week' => 'Semana', 'day' => 'Dia'] as $value => $label)
                <button
                    wire:click="setView('{{ $value }}')"
                    @class([
                        'px-3 py-1.5 rounded-t-md border-b-2 text-xs font-semibold',
                        'border-green-600 text-green-700 bg-green-50 dark:bg-green-500/10 dark:text-green-400' => $view === $value,
                        'border-transparent text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700/50' => $view !== $value,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="flex flex-wrap items-end gap-3">
            @if ($view !== 'list')
                <div class="flex items-center gap-1.5">
                    <button wire:click="previousPeriod" title="Anterior" class="p-1.5 rounded-md text-gray-500 dark:text-neutral-400 hover:bg-gray-100 dark:hover:bg-neutral-700">&larr;</button>
                    <button wire:click="goToday" class="px-2 py-1 rounded-md text-xs text-gray-600 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700">Hoje</button>
                    <button wire:click="nextPeriod" title="Próximo" class="p-1.5 rounded-md text-gray-500 dark:text-neutral-400 hover:bg-gray-100 dark:hover:bg-neutral-700">&rarr;</button>
                    <span class="text-xs font-medium text-gray-700 dark:text-neutral-200 ml-1 capitalize">{{ $periodLabel }}</span>
                </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar por título</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="pending">Pendentes</option>
                    <option value="done">Concluídas</option>
                    <option value="all">Todas</option>
                </select>
            </div>
        </div>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar tarefa' : 'Nova tarefa' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Título</label>
                <input type="text" wire:model="title" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('title') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Descrição (opcional)</label>
                <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Vencimento (opcional)</label>
                <input type="date" wire:model="due_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('due_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Vincular a um contato (opcional)</label>
                <select wire:model="contact_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— nenhum —</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Vincular a um lançamento em aberto (opcional)</label>
                <select wire:model="financial_entry_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— nenhum —</option>
                    @foreach ($financialEntries as $entry)
                        <option value="{{ $entry->id }}">{{ $entry->due_date->format('d/m/Y') }} — {{ $entry->description ?: 'sem descrição' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Salvar
                </button>
                <button type="button" wire:click="cancel" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>

    @if ($view === 'list')
        @if ($upcomingBirthdays->isNotEmpty())
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-3">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-2">Aniversários nos próximos 30 dias</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($upcomingBirthdays as $row)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-pink-50 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400">
                            {{ $row['contact']->name }} — {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m') }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Vencimento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tarefa</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Vínculo</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @forelse ($tasks as $task)
                        <tr wire:key="task-{{ $task->id }}">
                            <td @class([
                                'px-4 py-2 text-xs whitespace-nowrap',
                                'text-[#b42318] dark:text-[#ff453a] font-medium' => $task->isOverdue(),
                                'text-gray-700 dark:text-neutral-300' => ! $task->isOverdue(),
                            ])>
                                {{ $task->due_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                <span @class([
                                    'text-gray-900 dark:text-neutral-100' => $task->status === 'pending',
                                    'text-gray-400 dark:text-neutral-500 line-through' => $task->status === 'done',
                                ])>{{ $task->title }}</span>
                                @if ($task->description)
                                    <p class="text-gray-500 dark:text-neutral-400">{{ $task->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                                {{ collect([
                                    $task->contact?->name,
                                    $task->financialEntry ? ('lançamento: '.($task->financialEntry->description ?: $task->financialEntry->due_date->format('d/m/Y'))) : null,
                                ])->filter()->implode(' · ') ?: '—' }}
                            </td>
                            @if ($this->canWrite)
                                <td class="px-4 py-2 text-right text-xs whitespace-nowrap space-x-2">
                                    <button wire:click="toggleDone({{ $task->id }})" title="{{ $task->status === 'done' ? 'Reabrir' : 'Concluir' }}" class="inline-flex {{ $task->status === 'done' ? 'text-gray-500 dark:text-neutral-400' : 'text-[#15803d] dark:text-[#86efac]' }} hover:opacity-75"><x-icon name="check-circle" /></button>
                                    <button wire:click="edit({{ $task->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                    <button wire:click="delete({{ $task->id }})" wire:confirm="Tem certeza que quer excluir esta tarefa?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma tarefa encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $tasks->links() }}
        </div>
    @elseif ($view === 'month')
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                @foreach ($weekdayLabels as $label)
                    <div class="px-2 py-2 text-center">{{ $label }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($cells as $cell)
                    <button type="button" wire:click="goToDate('{{ $cell['date']->toDateString() }}')"
                        @class([
                            'border-t border-l border-gray-100 dark:border-neutral-700 p-1.5 min-h-[92px] align-top text-left hover:bg-gray-50 dark:hover:bg-neutral-700/40',
                            'bg-gray-50/60 dark:bg-neutral-900/40' => ! $cell['inCurrentMonth'],
                        ])>
                        <span @class([
                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-xs',
                            'bg-green-600 text-white font-semibold' => $cell['isToday'],
                            'text-gray-400 dark:text-neutral-500' => ! $cell['inCurrentMonth'] && ! $cell['isToday'],
                            'text-gray-700 dark:text-neutral-300' => $cell['inCurrentMonth'] && ! $cell['isToday'],
                        ])>{{ $cell['date']->day }}</span>

                        <div class="mt-1 space-y-0.5">
                            @foreach ($cell['birthdays']->take(2) as $row)
                                <p class="truncate text-xs px-1 py-0.5 rounded bg-pink-50 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400">{{ $row['contact']->name }}</p>
                            @endforeach
                            @foreach ($cell['tasks']->take(3) as $task)
                                <p @class([
                                    'truncate text-xs px-1 py-0.5 rounded',
                                    'bg-red-50 dark:bg-red-500/10 text-[#b42318] dark:text-[#ff453a]' => $task->isOverdue(),
                                    'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' => ! $task->isOverdue() && $task->status === 'pending',
                                    'bg-gray-100 dark:bg-neutral-700 text-gray-400 dark:text-neutral-500 line-through' => $task->status === 'done',
                                ])>{{ $task->title }}</p>
                            @endforeach
                            @if ($cell['tasks']->count() > 3)
                                <p class="text-xs text-gray-400 dark:text-neutral-500">+{{ $cell['tasks']->count() - 3 }}</p>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($view === 'week')
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <div class="grid grid-cols-7">
                @foreach ($cells as $cell)
                    <div class="border-l border-gray-100 dark:border-neutral-700 first:border-l-0">
                        <button type="button" wire:click="goToDate('{{ $cell['date']->toDateString() }}')"
                            class="w-full text-center py-2 border-b border-gray-100 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700/40">
                            <p class="text-xs text-gray-400 dark:text-neutral-500 uppercase">{{ $cell['date']->translatedFormat('D') }}</p>
                            <span @class([
                                'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs mt-0.5',
                                'bg-green-600 text-white font-semibold' => $cell['isToday'],
                                'text-gray-700 dark:text-neutral-300' => ! $cell['isToday'],
                            ])>{{ $cell['date']->day }}</span>
                        </button>
                        <div class="p-1.5 space-y-1 min-h-[160px]">
                            @foreach ($cell['birthdays'] as $row)
                                <p class="truncate text-xs px-1 py-0.5 rounded bg-pink-50 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400">{{ $row['contact']->name }}</p>
                            @endforeach
                            @foreach ($cell['tasks'] as $task)
                                <button wire:click="edit({{ $task->id }})" @class([
                                    'block w-full truncate text-left text-xs px-1 py-0.5 rounded',
                                    'bg-red-50 dark:bg-red-500/10 text-[#b42318] dark:text-[#ff453a]' => $task->isOverdue(),
                                    'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' => ! $task->isOverdue() && $task->status === 'pending',
                                    'bg-gray-100 dark:bg-neutral-700 text-gray-400 dark:text-neutral-500 line-through' => $task->status === 'done',
                                ])>{{ $task->title }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else {{-- day --}}
        @php $day = $cells->first(); @endphp
        <div class="space-y-3">
            @if ($day['birthdays']->isNotEmpty())
                <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-2">Aniversários</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($day['birthdays'] as $row)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-pink-50 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400">{{ $row['contact']->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
                @forelse ($day['tasks'] as $task)
                    <div wire:key="day-task-{{ $task->id }}" class="flex items-start justify-between gap-3 px-4 py-3 border-b last:border-b-0 border-gray-100 dark:border-neutral-700">
                        <div class="min-w-0">
                            <p @class([
                                'text-sm',
                                'text-gray-900 dark:text-neutral-100' => $task->status === 'pending',
                                'text-gray-400 dark:text-neutral-500 line-through' => $task->status === 'done',
                            ])>{{ $task->title }}</p>
                            @if ($task->description)
                                <p class="text-xs text-gray-500 dark:text-neutral-400 mt-0.5">{{ $task->description }}</p>
                            @endif
                            @if ($task->contact || $task->financialEntry)
                                <p class="text-xs text-gray-400 dark:text-neutral-500 mt-0.5">
                                    {{ collect([$task->contact?->name, $task->financialEntry ? 'lançamento vinculado' : null])->filter()->implode(' · ') }}
                                </p>
                            @endif
                        </div>
                        @if ($this->canWrite)
                            <div class="flex items-center gap-2 shrink-0">
                                <button wire:click="toggleDone({{ $task->id }})" title="{{ $task->status === 'done' ? 'Reabrir' : 'Concluir' }}" class="inline-flex {{ $task->status === 'done' ? 'text-gray-500 dark:text-neutral-400' : 'text-[#15803d] dark:text-[#86efac]' }} hover:opacity-75"><x-icon name="check-circle" /></button>
                                <button wire:click="edit({{ $task->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                <button wire:click="delete({{ $task->id }})" wire:confirm="Tem certeza que quer excluir esta tarefa?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nada agendado para esse dia.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
