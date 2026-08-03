<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />DRE — Demonstração de Resultado</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
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
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="font-medium text-gray-900 dark:text-neutral-100 mb-2">Receitas</p>
            <table class="w-full text-xs">
                <tbody>
                    @forelse ($income as $categoryName => $row)
                        <tr class="border-b border-gray-50 dark:border-neutral-700 last:border-0">
                            <td class="py-1.5 text-gray-600 dark:text-neutral-300">{{ $categoryName }}</td>
                            <td class="py-1.5 text-right text-[#15803d] dark:text-[#86efac]">{{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-1.5 text-gray-400 dark:text-neutral-500" colspan="2">Nenhuma receita no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 dark:border-neutral-600 font-semibold">
                        <td class="py-1.5 text-gray-900 dark:text-neutral-100">Total</td>
                        <td class="py-1.5 text-right text-[#15803d] dark:text-[#86efac]">{{ number_format($totalIncome, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="font-medium text-gray-900 dark:text-neutral-100 mb-2">Despesas</p>
            <table class="w-full text-xs">
                <tbody>
                    @forelse ($expense as $categoryName => $row)
                        <tr class="border-b border-gray-50 dark:border-neutral-700 last:border-0">
                            <td class="py-1.5 text-gray-600 dark:text-neutral-300">{{ $categoryName }}</td>
                            <td class="py-1.5 text-right text-[#b42318] dark:text-[#ff453a]">{{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-1.5 text-gray-400 dark:text-neutral-500" colspan="2">Nenhuma despesa no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 dark:border-neutral-600 font-semibold">
                        <td class="py-1.5 text-gray-900 dark:text-neutral-100">Total</td>
                        <td class="py-1.5 text-right text-[#b42318] dark:text-[#ff453a]">{{ number_format($totalExpense, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-4 bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 flex items-center justify-between">
        <p class="font-semibold text-gray-900 dark:text-neutral-100">Resultado do período</p>
        <p @class([
            'font-semibold text-lg',
            'text-[#15803d] dark:text-[#86efac]' => $result >= 0,
            'text-[#b42318] dark:text-[#ff453a]' => $result < 0,
        ])>
            {{ number_format($result, 2, ',', '.') }}
        </p>
    </div>
</div>
