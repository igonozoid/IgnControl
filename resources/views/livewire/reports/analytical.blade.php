<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />Relatório Analítico</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-green-600 dark:text-green-400 hover:underline">
            &larr; Relatórios
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">De</label>
                <input type="date" wire:model.live="from"
                    class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">Até</label>
                <input type="date" wire:model.live="to"
                    class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">Tipo</label>
                <select wire:model.live="type" class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
                    <option value="all">Receitas e despesas</option>
                    <option value="income">Só receitas</option>
                    <option value="expense">Só despesas</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Total receitas</p>
            <p class="text-lg font-semibold text-[#15803d] dark:text-[#86efac]">{{ number_format($totalIncome, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Total despesas</p>
            <p class="text-lg font-semibold text-[#b42318] dark:text-[#ff453a]">{{ number_format($totalExpense, 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                <tr>
                    <th class="text-left px-4 py-2">Vencimento</th>
                    <th class="text-left px-4 py-2">Descrição</th>
                    <th class="text-left px-4 py-2">Categoria</th>
                    <th class="text-left px-4 py-2">Centro de custo</th>
                    <th class="text-left px-4 py-2">Contato</th>
                    <th class="text-left px-4 py-2">Situação</th>
                    <th class="text-right px-4 py-2">Valor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-t border-gray-50 dark:border-neutral-700">
                        <td class="px-4 py-1.5 text-gray-600 dark:text-neutral-300">{{ $entry->due_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $entry->description ?: '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $entry->category?->name ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $entry->costCenter?->name ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $entry->contact?->name ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">
                            {{ ['pending' => 'Em aberto', 'paid' => 'Pago', 'canceled' => 'Cancelado'][$entry->status] ?? $entry->status }}
                        </td>
                        <td @class([
                            'px-4 py-1.5 text-right font-medium',
                            'text-[#15803d] dark:text-[#86efac]' => $entry->type === 'income',
                            'text-[#b42318] dark:text-[#ff453a]' => $entry->type === 'expense',
                        ])>{{ number_format($entry->amount, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="7">Nenhum lançamento no período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
