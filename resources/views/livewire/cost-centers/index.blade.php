<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="briefcase" class="w-4 h-4" />Centros de Custo</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo centro de custo
            </button>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar por nome</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>
        </div>
        <label class="mt-3 flex items-center gap-1.5 text-xs text-gray-600 dark:text-neutral-300">
            <input type="checkbox" wire:model.live="onlyNeedsReview" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
            Só pendentes de revisão (cadastrados rápido, direto do lançamento)
        </label>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar centro de custo' : 'Novo centro de custo' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Código (opcional)</label>
                    <input type="text" wire:model="code" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('code') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Aplica a</label>
                <div class="flex gap-4 text-gray-700 dark:text-neutral-300">
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="applies_to_expense" class="rounded dark:bg-neutral-700 dark:border-neutral-600"> Despesas</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="applies_to_revenue" class="rounded dark:bg-neutral-700 dark:border-neutral-600"> Receitas</label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Orçamento de despesa (opcional)</label>
                    <input type="number" step="0.01" min="0" wire:model="expense_budget" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('expense_budget') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Projeção de receita (opcional)</label>
                    <input type="number" step="0.01" min="0" wire:model="revenue_projection" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('revenue_projection') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <p class="text-gray-400 dark:text-neutral-500 -mt-2">Base pra um relatório de orçado x realizado futuro — não é usado em nenhum lançamento hoje.</p>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 dark:border-neutral-600 text-green-600">
                <label for="is_active" class="text-gray-700 dark:text-neutral-300">Ativo</label>
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Código</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Aplica a</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Situação</th>
                    @if ($this->canWrite)
                        <th class="px-4 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($costCenters as $costCenter)
                    <tr wire:key="cost-center-{{ $costCenter->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">
                            {{ $costCenter->name }}
                            @if ($costCenter->needs_review)
                                <span title="Cadastrado rápido, direto do lançamento — falta revisar" class="ml-1 inline-flex px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">revisar</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $costCenter->code ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            @if ($costCenter->applies_to_expense && $costCenter->applies_to_revenue)
                                Despesas e receitas
                            @elseif ($costCenter->applies_to_expense)
                                Só despesas
                            @elseif ($costCenter->applies_to_revenue)
                                Só receitas
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs">
                            @if ($costCenter->is_active)
                                <span class="text-[#15803d] dark:text-[#86efac]">Ativo</span>
                            @else
                                <span class="text-gray-400 dark:text-neutral-500">Inativo</span>
                            @endif
                        </td>
                        @if ($this->canWrite)
                            <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                @if ($costCenter->needs_review)
                                    <button wire:click="markReviewed({{ $costCenter->id }})" title="Marcar como revisado" class="inline-flex text-amber-600 dark:text-amber-400 hover:text-amber-800"><x-icon name="check-circle" /></button>
                                @endif
                                <button wire:click="edit({{ $costCenter->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                <button wire:click="delete({{ $costCenter->id }})" wire:confirm="Tem certeza que quer excluir este centro de custo?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum centro de custo cadastrado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $costCenters->links() }}
    </div>
</div>
