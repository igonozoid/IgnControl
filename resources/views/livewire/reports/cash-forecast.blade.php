<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />Previsão de Caixa</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-green-600 dark:text-green-400 hover:underline">
            &larr; Relatórios
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">Projetar até</label>
                <input type="date" wire:model.live="to"
                    class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Saldo atual (realizado)</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">{{ number_format($currentBalance, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Pior saldo projetado no período</p>
            <p @class([
                'text-lg font-semibold',
                'text-[#15803d] dark:text-[#86efac]' => $lowestBalance >= 0,
                'text-[#b42318] dark:text-[#ff453a]' => $lowestBalance < 0,
            ])>{{ number_format($lowestBalance, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Saldo projetado final</p>
            <p @class([
                'text-lg font-semibold',
                'text-[#15803d] dark:text-[#86efac]' => $projectedBalance >= 0,
                'text-[#b42318] dark:text-[#ff453a]' => $projectedBalance < 0,
            ])>{{ number_format($projectedBalance, 2, ',', '.') }}</p>
        </div>
    </div>

    @if ($lowestBalance < 0)
        <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs rounded-lg px-3 py-2 mb-4">
            <x-icon name="lock" class="w-4 h-4" />
            O saldo projetado fica negativo em algum momento do período — vale conferir os vencimentos abaixo.
        </div>
    @endif

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                <tr>
                    <th class="text-left px-4 py-2">Data</th>
                    <th class="text-right px-4 py-2">A receber</th>
                    <th class="text-right px-4 py-2">A pagar</th>
                    <th class="text-right px-4 py-2">Líquido</th>
                    <th class="text-right px-4 py-2">Saldo projetado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($days as $day)
                    <tr class="border-t border-gray-50 dark:border-neutral-700">
                        <td class="px-4 py-1.5 text-gray-600 dark:text-neutral-300">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-1.5 text-right text-[#15803d] dark:text-[#86efac]">{{ number_format($day['income'], 2, ',', '.') }}</td>
                        <td class="px-4 py-1.5 text-right text-[#b42318] dark:text-[#ff453a]">{{ number_format($day['expense'], 2, ',', '.') }}</td>
                        <td class="px-4 py-1.5 text-right text-gray-700 dark:text-neutral-300">{{ number_format($day['net'], 2, ',', '.') }}</td>
                        <td @class([
                            'px-4 py-1.5 text-right font-medium',
                            'text-[#15803d] dark:text-[#86efac]' => $day['balance'] >= 0,
                            'text-[#b42318] dark:text-[#ff453a]' => $day['balance'] < 0,
                        ])>{{ number_format($day['balance'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="5">Nenhum lançamento pendente no período.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
