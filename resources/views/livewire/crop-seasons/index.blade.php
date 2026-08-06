<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="calendar" class="w-4 h-4" />Safras</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Nova safra
            </button>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Talhão</label>
                <select wire:model.live="filterField" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Status</label>
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach (\App\Models\CropSeason::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar safra' : 'Nova safra' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Talhão</label>
                <select wire:model="field_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— selecione —</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                    @endforeach
                </select>
                @error('field_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Cultura</label>
                    <input type="text" wire:model="crop_name" placeholder="Soja, Milho, Café..." class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('crop_name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Variedade (opcional)</label>
                    <input type="text" wire:model="variety" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Identificação da safra</label>
                <input type="text" wire:model="season_label" placeholder="Safra 2025/2026" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('season_label') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data de plantio (opcional)</label>
                    <input type="date" wire:model="planting_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Previsão de colheita (opcional)</label>
                    <input type="date" wire:model="expected_harvest_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Área plantada (opcional)</label>
                    <input type="number" step="0.01" min="0" wire:model="planted_area" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Unidade</label>
                    <input type="text" wire:model="area_unit" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Status</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="planned">Planejada</option>
                        <option value="planted">Plantada</option>
                        <option value="growing">Em desenvolvimento</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                    <p class="text-gray-400 dark:text-neutral-500 mt-1">"Colhida" só pelo botão dedicado.</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Produtividade esperada (opcional)</label>
                    <input type="number" step="0.001" min="0" wire:model="expected_yield" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Unidade de produtividade</label>
                    <input type="text" wire:model="yield_unit" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Produto no Estoque (opcional)</label>
                <select wire:model="harvested_product_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— nenhum —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <p class="text-gray-400 dark:text-neutral-500 mt-1">Ao marcar como colhida, entra uma movimentação de estoque com a produtividade real.</p>
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

    <x-slide-over show="showHarvestForm" close="closeHarvestForm" title="Marcar como colhida">
        <form wire:submit="markHarvested" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Data da colheita</label>
                <input type="date" wire:model="actual_harvest_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('actual_harvest_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Produtividade real</label>
                <input type="number" step="0.001" min="0" wire:model="actual_yield" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('actual_yield') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                <p class="text-gray-400 dark:text-neutral-500 mt-1">Se a safra tiver produto vinculado, gera entrada no Estoque com essa quantidade.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Confirmar colheita
                </button>
                <button type="button" wire:click="closeHarvestForm" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Safra</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Cultura</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Talhão</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Produtividade</th>
                    @if ($this->canWrite)
                        <th class="px-4 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($seasons as $season)
                    <tr wire:key="crop-season-{{ $season->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $season->season_label }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $season->crop_name }}{{ $season->variety ? ' — '.$season->variety : '' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $season->field?->name }}</td>
                        <td class="px-4 py-2 text-xs">
                            <span @class([
                                'px-1.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' => in_array($season->status, ['planned', 'cancelled']),
                                'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => in_array($season->status, ['planted', 'growing']),
                                'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' => $season->status === 'harvested',
                            ])>{{ \App\Models\CropSeason::STATUSES[$season->status] ?? $season->status }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            @if ($season->actual_yield)
                                {{ number_format($season->actual_yield, 3, ',', '.') }} {{ $season->yield_unit }}
                            @elseif ($season->expected_yield)
                                ~{{ number_format($season->expected_yield, 3, ',', '.') }} {{ $season->yield_unit }}
                            @else
                                —
                            @endif
                        </td>
                        @if ($this->canWrite)
                            <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                @if ($season->status !== 'harvested')
                                    <button wire:click="edit({{ $season->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                    <button wire:click="openHarvestForm({{ $season->id }})" title="Marcar como colhida" class="inline-flex text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"><x-icon name="check-circle" /></button>
                                @else
                                    <button wire:click="reopenSeason({{ $season->id }})" wire:confirm="Reabrir esta safra? Isso estorna a entrada de estoque da colheita." title="Reabrir" class="inline-flex text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300"><x-icon name="x-circle" /></button>
                                @endif
                                @if (in_array($season->status, ['planned', 'cancelled']))
                                    <button wire:click="delete({{ $season->id }})" wire:confirm="Tem certeza que quer excluir esta safra?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma safra cadastrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $seasons->links() }}
    </div>
</div>
