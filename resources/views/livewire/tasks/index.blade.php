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

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
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

    <div class="mt-3">
        {{ $tasks->links() }}
    </div>
</div>
