<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />DRE — Demonstração de Resultado</h1>
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
        </div>
        <p class="text-xs text-gray-400 dark:text-neutral-500 mt-2">
            Calculado por regime de competência (data de movimento de cada lançamento), não pela data de vencimento.
        </p>
    </div>

    <div class="space-y-3">
        @forelse ($sections as $section)
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-neutral-700/50">
                    <p class="text-xs font-semibold text-gray-700 dark:text-neutral-200 uppercase">{{ $section['label'] }}</p>
                    <p @class([
                        'text-xs font-semibold',
                        'text-[#15803d] dark:text-[#86efac]' => $section['total'] >= 0,
                        'text-[#b42318] dark:text-[#ff453a]' => $section['total'] < 0,
                    ])>
                        {{ number_format($section['total'], 2, ',', '.') }}
                    </p>
                </div>
                <table class="w-full text-xs">
                    <tbody>
                        @foreach ($section['income'] as $categoryName => $total)
                            <tr class="border-b border-gray-50 dark:border-neutral-700 last:border-0">
                                <td class="py-1.5 px-4 text-gray-600 dark:text-neutral-300">{{ $categoryName }}</td>
                                <td class="py-1.5 px-4 text-right text-[#15803d] dark:text-[#86efac] w-40">{{ number_format($total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        @foreach ($section['expense'] as $categoryName => $total)
                            <tr class="border-b border-gray-50 dark:border-neutral-700 last:border-0">
                                <td class="py-1.5 px-4 text-gray-600 dark:text-neutral-300">{{ $categoryName }}</td>
                                <td class="py-1.5 px-4 text-right text-[#b42318] dark:text-[#ff453a] w-40">-{{ number_format($total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 text-center text-xs text-gray-500 dark:text-neutral-400">
                Nenhum lançamento com competência no período selecionado.
            </div>
        @endforelse
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Total de receitas</p>
            <p class="text-sm font-semibold text-[#15803d] dark:text-[#86efac]">{{ number_format($totalIncome, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Total de despesas</p>
            <p class="text-sm font-semibold text-[#b42318] dark:text-[#ff453a]">{{ number_format($totalExpense, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 flex flex-col justify-center">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Resultado do período</p>
            <p @class([
                'text-sm font-semibold',
                'text-[#15803d] dark:text-[#86efac]' => $result >= 0,
                'text-[#b42318] dark:text-[#ff453a]' => $result < 0,
            ])>
                {{ number_format($result, 2, ',', '.') }}
            </p>
        </div>
    </div>
</div>
