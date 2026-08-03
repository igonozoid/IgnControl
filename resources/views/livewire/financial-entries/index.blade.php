<div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="list" class="w-4 h-4" />Lançamentos Financeiros</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo lançamento
            </button>
        @endif
    </div>

    {{-- Abas: Despesas / Receitas / Transferências --}}
    <div class="border-b border-gray-200 dark:border-neutral-700 mb-3">
        <nav class="-mb-px flex space-x-1">
            @foreach (['expense' => 'Despesas', 'income' => 'Receitas', 'transfer' => 'Transferências'] as $value => $label)
                <button
                    wire:click="$set('tab', '{{ $value }}')"
                    @class([
                        'px-3 py-1.5 rounded-t-md border-b-2 text-xs font-semibold',
                        'border-green-600 text-green-700 bg-green-50 dark:bg-green-500/10 dark:text-green-400' => $tab === $value,
                        'border-transparent text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700/50' => $tab !== $value,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Barra de filtros --}}
    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Mês</label>
                <select wire:model.live="month" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Ano</label>
                <select wire:model.live="year" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach (range(now()->year, now()->year - 4) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="all">Todas</option>
                    <option value="pending">Em aberto</option>
                    <option value="paid">Pago</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Conta</label>
                <select wire:model.live="filterAccountId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas as contas</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($tab !== 'transfer')
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Categoria</label>
                    <select wire:model.live="filterCategoryId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Contato</label>
                    <select wire:model.live="filterContactId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="">Todos</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-span-2 sm:col-span-3 lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar (descrição/contato)</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
        </div>
    </div>

    {{-- Resumo --}}
    <div class="grid grid-cols-2 gap-3 mb-3">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Em aberto (filtro atual)</p>
            <p class="text-base font-semibold text-[#b45309] dark:text-[#fbbf24]">{{ number_format((float) $totalPending, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Pago (filtro atual)</p>
            <p class="text-base font-semibold text-gray-700 dark:text-neutral-300">{{ number_format((float) $totalPaid, 2, ',', '.') }}</p>
        </div>
    </div>

    @if ($lockError)
        <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs rounded-lg px-3 py-2 mb-3">
            <x-icon name="lock" class="w-4 h-4" />
            {{ $lockError }}
        </div>
    @endif

    {{-- Formulário --}}
    <x-slide-over show="showForm" close="cancel" title="{{ ($editingId ? 'Editar lançamento' : 'Novo lançamento') . ' — ' . ['expense' => 'Despesa', 'income' => 'Receita', 'transfer' => 'Transferência'][$tab] }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">
                    {{ $tab === 'transfer' ? 'Conta de origem' : 'Conta' }}
                </label>
                <select wire:model="financial_account_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">— selecione —</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                @error('financial_account_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            @if ($tab === 'transfer')
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Conta de destino</label>
                    <select wire:model="destination_account_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— selecione —</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('destination_account_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            @else
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Contato</label>
                        @if (! $showQuickContact)
                            <button type="button" wire:click="$set('showQuickContact', true)" title="Cadastrar contato novo" class="text-green-600 dark:text-green-400 hover:text-green-800"><x-icon name="plus" class="w-3.5 h-3.5" /></button>
                        @endif
                    </div>
                    @if ($showQuickContact)
                        <div class="mt-1 flex gap-1.5">
                            <input type="text" wire:model="quickContactName" placeholder="Nome do contato" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <button type="button" wire:click="quickCreateContact" class="px-2 rounded-md bg-green-600 text-white text-xs hover:bg-green-700"><x-icon name="check" class="w-3.5 h-3.5" /></button>
                            <button type="button" wire:click="$set('showQuickContact', false)" class="px-2 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-neutral-300 text-xs"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
                        </div>
                        @error('quickContactName') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    @else
                        <select wire:model="contact_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}{{ $contact->needs_review ? ' (revisar cadastro)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('contact_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block font-medium text-gray-700 dark:text-neutral-300">Categoria</label>
                            @if (! $showQuickCategory)
                                <button type="button" wire:click="$set('showQuickCategory', true)" title="Cadastrar categoria nova" class="text-green-600 dark:text-green-400 hover:text-green-800"><x-icon name="plus" class="w-3.5 h-3.5" /></button>
                            @endif
                        </div>
                        @if ($showQuickCategory)
                            <div class="mt-1 flex gap-1.5">
                                <input type="text" wire:model="quickCategoryName" placeholder="Nome" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <button type="button" wire:click="quickCreateCategory" class="px-2 rounded-md bg-green-600 text-white text-xs hover:bg-green-700"><x-icon name="check" class="w-3.5 h-3.5" /></button>
                                <button type="button" wire:click="$set('showQuickCategory', false)" class="px-2 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-neutral-300 text-xs"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
                            </div>
                            @error('quickCategoryName') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        @else
                            <select wire:model="category_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <option value="">— nenhuma —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}{{ $category->needs_review ? ' (revisar)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block font-medium text-gray-700 dark:text-neutral-300">Centro de custo</label>
                            @if (! $showQuickCostCenter)
                                <button type="button" wire:click="$set('showQuickCostCenter', true)" title="Cadastrar centro de custo novo" class="text-green-600 dark:text-green-400 hover:text-green-800"><x-icon name="plus" class="w-3.5 h-3.5" /></button>
                            @endif
                        </div>
                        @if ($showQuickCostCenter)
                            <div class="mt-1 flex gap-1.5">
                                <input type="text" wire:model="quickCostCenterName" placeholder="Nome" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <button type="button" wire:click="quickCreateCostCenter" class="px-2 rounded-md bg-green-600 text-white text-xs hover:bg-green-700"><x-icon name="check" class="w-3.5 h-3.5" /></button>
                                <button type="button" wire:click="$set('showQuickCostCenter', false)" class="px-2 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-neutral-300 text-xs"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
                            </div>
                            @error('quickCostCenterName') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        @else
                            <select wire:model="cost_center_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <option value="">— nenhum —</option>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->name }}{{ $costCenter->needs_review ? ' (revisar)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('cost_center_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        @endif
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Moeda</label>
                    <select wire:model="currency_code" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor</label>
                    <input type="number" step="0.01" wire:model="amount" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('amount') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Vencimento</label>
                    <input type="date" wire:model="due_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('due_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Pago em (opcional)</label>
                    <input type="date" wire:model="paid_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('paid_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Descrição</label>
                <input type="text" wire:model="description" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('description') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
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

    {{-- Listagem --}}
    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Vencimento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Descrição</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">
                        {{ $tab === 'transfer' ? 'Contas' : 'Contato' }}
                    </th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Valor</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Situação</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($entries as $entry)
                    @php $visualStatus = $this->statusFor($entry); @endphp
                    <tr wire:key="entry-{{ $entry->id }}">
                        <td class="px-4 py-2 text-xs text-gray-700 dark:text-neutral-300">{{ $entry->due_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $entry->description ?: '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            @if ($tab === 'transfer')
                                {{ $entry->financialAccount?->name }} → {{ $entry->destinationAccount?->name }}
                            @else
                                {{ $entry->contact?->name ?? '—' }}
                            @endif
                        </td>
                        {{-- Cores herdadas do sistema legado: verde = pendente no
                             prazo, vermelho = atrasado, neutro = já pago. --}}
                        <td @class([
                            'px-4 py-2 text-xs text-right font-medium',
                            'text-[#b42318] dark:text-[#ff453a]' => $visualStatus === 'overdue',
                            'text-[#15803d] dark:text-[#86efac]' => $visualStatus === 'pending',
                            'text-gray-500 dark:text-neutral-400' => $visualStatus === 'paid' || $visualStatus === 'canceled',
                        ])>
                            {{ number_format((float) $entry->amount, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-xs">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-red-100 text-[#b42318] dark:bg-red-500/10 dark:text-[#ff453a]' => $visualStatus === 'overdue',
                                'bg-green-100 text-[#15803d] dark:bg-green-500/10 dark:text-[#86efac]' => $visualStatus === 'pending',
                                'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' => $visualStatus === 'paid',
                                'bg-gray-100 text-gray-400 line-through dark:bg-neutral-700 dark:text-neutral-500' => $visualStatus === 'canceled',
                            ])>
                                {{ ['overdue' => 'Atrasado', 'pending' => 'Em aberto', 'paid' => 'Pago', 'canceled' => 'Cancelado'][$visualStatus] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right text-xs whitespace-nowrap space-x-2">
                            <a href="{{ route('financial-entries.receipt', $entry) }}" target="_blank" title="Imprimir recibo" class="inline-flex text-gray-500 dark:text-neutral-400 hover:text-gray-800 dark:hover:text-neutral-100"><x-icon name="printer" /></a>
                            @if ($this->canWrite)
                                @if ($entry->status === 'pending')
                                    <button wire:click="markAsPaid({{ $entry->id }})" title="Baixar" class="inline-flex text-[#15803d] dark:text-[#86efac] hover:opacity-75"><x-icon name="check-circle" /></button>
                                @endif
                                <button wire:click="edit({{ $entry->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                <button wire:click="delete({{ $entry->id }})" wire:confirm="Tem certeza que quer excluir este lançamento?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum lançamento encontrado com esses filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $entries->links() }}
    </div>
</div>
