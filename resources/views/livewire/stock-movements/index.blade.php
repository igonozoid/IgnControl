<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="briefcase" class="w-4 h-4" />Movimentações de Estoque</h1>
        @if ($this->canWrite)
            <div class="flex gap-2">
                <button wire:click="openTransferForm" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md text-xs font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="arrow-top-right-on-square" class="w-3.5 h-3.5" />
                    Transferência
                </button>
                <button wire:click="openMovementForm" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                    <x-icon name="plus" />
                    Nova movimentação
                </button>
            </div>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-2">Saldo disponível por produto</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-neutral-400">
                        <th class="pr-4 py-1">Produto</th>
                        <th class="pr-4 py-1 text-right">Disponível</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balance as $row)
                        <tr wire:key="balance-{{ $row['product']->id }}" class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="pr-4 py-1 text-gray-700 dark:text-neutral-200">{{ $row['product']->name }}</td>
                            <td @class([
                                'pr-4 py-1 text-right font-medium',
                                'text-[#b42318] dark:text-[#ff453a]' => $row['available'] < 0,
                                'text-gray-700 dark:text-neutral-200' => $row['available'] >= 0,
                            ])>{{ number_format($row['available'], 3, ',', '.') }} {{ $row['product']->unit_code }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-3 text-gray-400 dark:text-neutral-500">Nenhum produto controla estoque ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-slide-over show="showMovementForm" close="cancel" title="Nova movimentação">
        <form wire:submit="saveMovement" class="space-y-3 text-xs">
            @if ($formError)
                <div class="rounded-md bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-amber-700 dark:text-amber-400">
                    {{ $formError }}
                </div>
            @endif
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Produto</label>
                <select wire:model="product_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— selecione —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('product_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo</label>
                    <select wire:model="movement_type" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @foreach (\App\Models\StockMovement::MANUAL_TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('movement_type') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Local (opcional)</label>
                    <select wire:model="location_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— nenhum —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                    <input type="date" wire:model="movement_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('movement_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Quantidade</label>
                    <input type="number" step="0.001" min="0" wire:model="quantity" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('quantity') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Custo unit. (opcional)</label>
                    <input type="number" step="0.0001" min="0" wire:model="unit_cost" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
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

    <x-slide-over show="showTransferForm" close="cancel" title="Transferência entre locais">
        <form wire:submit="saveTransfer" class="space-y-3 text-xs">
            @if ($formError)
                <div class="rounded-md bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-amber-700 dark:text-amber-400">
                    {{ $formError }}
                </div>
            @endif
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Produto</label>
                <select wire:model="transfer_product_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— selecione —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('transfer_product_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">De (origem)</label>
                    <select wire:model="transfer_from_location_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— selecione —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('transfer_from_location_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Para (destino)</label>
                    <select wire:model="transfer_to_location_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— selecione —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('transfer_to_location_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                    <input type="date" wire:model="transfer_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('transfer_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Quantidade</label>
                    <input type="number" step="0.001" min="0" wire:model="transfer_quantity" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('transfer_quantity') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações (opcional)</label>
                <textarea wire:model="transfer_notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Transferir
                </button>
                <button type="button" wire:click="cancel" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Produto</label>
                <select wire:model.live="filterProductId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Local</label>
                <select wire:model.live="filterLocationId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Tipo</label>
                <select wire:model.live="filterType" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($allTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-per-page-selector />
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Produto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Local</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tipo</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Quantidade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($movements as $movement)
                    <tr wire:key="movement-{{ $movement->id }}">
                        <td class="px-4 py-2 text-xs text-gray-700 dark:text-neutral-300 whitespace-nowrap">{{ $movement->movement_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $movement->product->name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $movement->location?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ \App\Models\StockMovement::typeLabel($movement->movement_type) }}</td>
                        <td @class([
                            'px-4 py-2 text-right text-xs font-medium whitespace-nowrap',
                            'text-[#15803d] dark:text-[#86efac]' => in_array($movement->movement_type, \App\Models\StockMovement::INBOUND_TYPES, true),
                            'text-[#b42318] dark:text-[#ff453a]' => in_array($movement->movement_type, \App\Models\StockMovement::OUTBOUND_TYPES, true),
                        ])>
                            {{ in_array($movement->movement_type, \App\Models\StockMovement::INBOUND_TYPES, true) ? '+' : '-' }}{{ number_format($movement->quantity, 3, ',', '.') }} {{ $movement->product->unit_code }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma movimentação encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $movements->links() }}
    </div>
</div>
