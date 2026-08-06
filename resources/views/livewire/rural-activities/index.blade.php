<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8 space-y-6">
    <div>
        <div class="flex items-center justify-between mb-4">
            <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="list" class="w-4 h-4" />Atividades</h1>
            @if ($this->canWrite)
                <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                    <x-icon name="plus" />
                    Nova atividade
                </button>
            @endif
        </div>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Talhão</label>
                    <select wire:model.live="filterFieldActivities" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="">Todos</option>
                        @foreach ($fields as $field)
                            <option value="{{ $field->id }}">{{ $field->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Status</label>
                    <select wire:model.live="filterStatusActivities" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="">Todos</option>
                        @foreach (\App\Models\RuralActivity::STATUSES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar atividade' : 'Nova atividade' }}">
            <form wire:submit="save" class="space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo</label>
                        <select wire:model="activity_type" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            @foreach (\App\Models\RuralActivity::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Status</label>
                        <select wire:model="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="planned">Planejada</option>
                            <option value="in_progress">Em andamento</option>
                            <option value="done">Concluída</option>
                        </select>
                        <p class="text-gray-400 dark:text-neutral-500 mt-1">Concluída já baixa o insumo do estoque.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Talhão (opcional)</label>
                        <select wire:model="field_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($fields as $field)
                                <option value="{{ $field->id }}">{{ $field->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Safra (opcional)</label>
                        <select wire:model="crop_season_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhuma —</option>
                            @foreach ($seasons as $season)
                                <option value="{{ $season->id }}">{{ $season->season_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Ativo envolvido (opcional)</label>
                        <select wire:model="asset_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Responsável (opcional)</label>
                        <select wire:model="responsible_contact_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Data prevista (opcional)</label>
                        <input type="date" wire:model="scheduled_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Data realizada (opcional)</label>
                        <input type="date" wire:model="performed_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-semibold text-gray-700 dark:text-neutral-300">Insumos</p>
                        <button type="button" wire:click="addItemRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
                    </div>
                    @foreach ($itemRows as $i => $row)
                        <div wire:key="rural-item-row-{{ $i }}" class="grid grid-cols-12 gap-1.5 items-start mb-2">
                            <select wire:model="itemRows.{{ $i }}.product_id" class="col-span-7 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <option value="">Produto</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" min="0" placeholder="Qtd." wire:model="itemRows.{{ $i }}.quantity" class="col-span-4 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <button type="button" wire:click="removeItemRow({{ $i }})" title="Remover" class="col-span-1 shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                        </div>
                    @endforeach
                    @if (empty($itemRows))
                        <p class="text-gray-400 dark:text-neutral-500">Nenhum insumo adicionado.</p>
                    @endif
                </div>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações (opcional)</label>
                    <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
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
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Talhão / Safra</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Status</th>
                        @if ($this->canWrite)
                            <th class="px-4 py-2"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @forelse ($activities as $activity)
                        <tr wire:key="rural-activity-{{ $activity->id }}">
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-neutral-300 whitespace-nowrap">{{ ($activity->performed_date ?? $activity->scheduled_date)?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ \App\Models\RuralActivity::TYPES[$activity->activity_type] ?? $activity->activity_type }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ collect([$activity->field?->name, $activity->cropSeason?->season_label])->filter()->implode(' / ') ?: '—' }}</td>
                            <td class="px-4 py-2 text-xs">
                                <span @class([
                                    'px-1.5 py-0.5 rounded-full text-xs font-medium',
                                    'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' => $activity->status === 'planned',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $activity->status === 'in_progress',
                                    'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' => $activity->status === 'done',
                                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $activity->status === 'cancelled',
                                ])>{{ \App\Models\RuralActivity::STATUSES[$activity->status] ?? $activity->status }}</span>
                            </td>
                            @if ($this->canWrite)
                                <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                    @if ($activity->status !== 'cancelled')
                                        <button wire:click="edit({{ $activity->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                    @endif
                                    @if ($activity->status === 'planned')
                                        <button wire:click="delete({{ $activity->id }})" wire:confirm="Tem certeza que quer excluir esta atividade?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                                    @elseif ($activity->status !== 'cancelled')
                                        <button wire:click="cancelActivity({{ $activity->id }})" wire:confirm="Cancelar esta atividade? Isso estorna o insumo já consumido." title="Cancelar" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="x-circle" /></button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma atividade cadastrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $activities->links() }}
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-neutral-700 pt-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="shield" class="w-4 h-4" />Ocorrências</h2>
            @if ($this->canWrite)
                <button wire:click="createOccurrence" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                    <x-icon name="plus" />
                    Nova ocorrência
                </button>
            @endif
        </div>

        <x-slide-over show="showOccurrenceForm" close="cancelOccurrenceForm" title="{{ $editingOccurrenceId ? 'Editar ocorrência' : 'Nova ocorrência' }}">
            <form wire:submit="saveOccurrence" class="space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Talhão (opcional)</label>
                        <select wire:model="occ_field_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($fields as $field)
                                <option value="{{ $field->id }}">{{ $field->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Safra (opcional)</label>
                        <select wire:model="occ_crop_season_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhuma —</option>
                            @foreach ($seasons as $season)
                                <option value="{{ $season->id }}">{{ $season->season_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                        <input type="date" wire:model="occurrence_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @error('occurrence_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo</label>
                        <select wire:model="occurrence_type" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            @foreach (\App\Models\RuralOccurrence::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Severidade</label>
                        <select wire:model="severity" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            @foreach (\App\Models\RuralOccurrence::SEVERITIES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Descrição</label>
                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
                    @error('description') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Ação tomada (opcional)</label>
                    <textarea wire:model="action_taken" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Status</label>
                    <select wire:model="occ_status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @foreach (\App\Models\RuralOccurrence::STATUSES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações (opcional)</label>
                    <textarea wire:model="occ_notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                        <x-icon name="check" />
                        Salvar
                    </button>
                    <button type="button" wire:click="cancelOccurrenceForm" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                        <x-icon name="x-mark" />
                        Cancelar
                    </button>
                </div>
            </form>
        </x-slide-over>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Talhão</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Severidade</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Status</th>
                        @if ($this->canWrite)
                            <th class="px-4 py-2"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @forelse ($occurrences as $occurrence)
                        <tr wire:key="rural-occurrence-{{ $occurrence->id }}">
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-neutral-300 whitespace-nowrap">{{ $occurrence->occurrence_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ \App\Models\RuralOccurrence::TYPES[$occurrence->occurrence_type] ?? $occurrence->occurrence_type }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $occurrence->field?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs">
                                <span @class([
                                    'px-1.5 py-0.5 rounded-full text-xs font-medium',
                                    'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' => in_array($occurrence->severity, ['low', 'normal']),
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $occurrence->severity === 'high',
                                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $occurrence->severity === 'critical',
                                ])>{{ \App\Models\RuralOccurrence::SEVERITIES[$occurrence->severity] ?? $occurrence->severity }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ \App\Models\RuralOccurrence::STATUSES[$occurrence->status] ?? $occurrence->status }}</td>
                            @if ($this->canWrite)
                                <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                    <button wire:click="editOccurrence({{ $occurrence->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                    <button wire:click="deleteOccurrence({{ $occurrence->id }})" wire:confirm="Tem certeza que quer excluir esta ocorrência?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma ocorrência registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $occurrences->links() }}
        </div>
    </div>
</div>
