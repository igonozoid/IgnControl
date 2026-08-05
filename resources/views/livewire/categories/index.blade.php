<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="tag" class="w-4 h-4" />Categorias</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Nova categoria
            </button>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar por nome</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Tipo</label>
                <select wire:model.live="filterType" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    <option value="income">Receita</option>
                    <option value="expense">Despesa</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    <option value="active">Ativas</option>
                    <option value="inactive">Inativas</option>
                </select>
            </div>
        </div>
        <label class="mt-3 flex items-center gap-1.5 text-xs text-gray-600 dark:text-neutral-300">
            <input type="checkbox" wire:model.live="onlyNeedsReview" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
            Só pendentes de revisão (cadastradas rápido, direto do lançamento)
        </label>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar categoria' : 'Nova categoria' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo</label>
                    <select wire:model="type" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="income">Receita</option>
                        <option value="expense">Despesa</option>
                    </select>
                    @error('type') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Categoria pai (opcional)</label>
                    <select wire:model="parent_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— nenhuma —</option>
                        @foreach ($parentOptions as $option)
                            @if ($option->id !== $editingId)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Seção do DRE (opcional)</label>
                <select wire:model="dre_group" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— inferir automaticamente pelo nome —</option>
                    @foreach (\App\Models\Category::DRE_GROUPS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-gray-400 dark:text-neutral-500 mt-1">Deixe em branco pra o DRE classificar sozinho pela palavra-chave do nome (ex.: "Juros" cai em Resultado Financeiro).</p>
                @error('dre_group') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 dark:border-neutral-600 text-green-600">
                <label for="is_active" class="text-gray-700 dark:text-neutral-300">Ativa</label>
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tipo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Categoria pai</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Situação</th>
                    @if ($this->canWrite)
                        <th class="px-4 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">
                            {{ $category->name }}
                            @if ($category->needs_review)
                                <span title="Cadastrada rápido, direto do lançamento — falta revisar" class="ml-1 inline-flex px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">revisar</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $category->type === 'income' ? 'Receita' : 'Despesa' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">
                            @if ($category->is_active)
                                <span class="text-[#15803d] dark:text-[#86efac]">Ativa</span>
                            @else
                                <span class="text-gray-400 dark:text-neutral-500">Inativa</span>
                            @endif
                        </td>
                        @if ($this->canWrite)
                            <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                @if ($category->needs_review)
                                    <button wire:click="markReviewed({{ $category->id }})" title="Marcar como revisado" class="inline-flex text-amber-600 dark:text-amber-400 hover:text-amber-800"><x-icon name="check-circle" /></button>
                                @endif
                                <button wire:click="edit({{ $category->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                <button wire:click="delete({{ $category->id }})" wire:confirm="Tem certeza que quer excluir esta categoria?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma categoria cadastrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $categories->links() }}
    </div>
</div>
