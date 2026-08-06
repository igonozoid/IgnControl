<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="sprout" class="w-4 h-4" />Custos por Talhão/Ativo</h1>
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
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <p class="text-xs text-gray-500 dark:text-neutral-400">Custo total de insumo no período</p>
        <p class="text-lg font-semibold text-[#b42318] dark:text-[#ff453a]">{{ number_format($totalCost, 2, ',', '.') }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <p class="px-4 py-2 bg-gray-50 dark:bg-neutral-700/50 text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Custo por talhão</p>
            <table class="w-full text-xs">
                <tbody>
                    @forelse ($byField as $field => $cost)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $field }}</td>
                            <td class="px-4 py-1.5 text-right text-gray-900 dark:text-neutral-100">{{ number_format($cost, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="2">Nenhum consumo com talhão vinculado no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <p class="px-4 py-2 bg-gray-50 dark:bg-neutral-700/50 text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Custo por ativo</p>
            <table class="w-full text-xs">
                <tbody>
                    @forelse ($byAsset as $asset => $cost)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $asset }}</td>
                            <td class="px-4 py-1.5 text-right text-gray-900 dark:text-neutral-100">{{ number_format($cost, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="2">Nenhum consumo com ativo vinculado no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                <tr>
                    <th class="text-left px-4 py-2">Data</th>
                    <th class="text-left px-4 py-2">Insumo</th>
                    <th class="text-left px-4 py-2">Atividade</th>
                    <th class="text-left px-4 py-2">Talhão</th>
                    <th class="text-left px-4 py-2">Safra</th>
                    <th class="text-left px-4 py-2">Ativo</th>
                    <th class="text-right px-4 py-2">Qtd.</th>
                    <th class="text-right px-4 py-2">Custo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-gray-50 dark:border-neutral-700">
                        <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200 whitespace-nowrap">{{ $row['date']->format('d/m/Y') }}</td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $row['product'] }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row['activity_type'] ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row['field'] ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row['season'] ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row['asset'] ?? '—' }}</td>
                        <td class="px-4 py-1.5 text-right text-gray-700 dark:text-neutral-200">{{ number_format($row['quantity'], 3, ',', '.') }} {{ $row['unit_code'] }}</td>
                        <td class="px-4 py-1.5 text-right text-gray-900 dark:text-neutral-100">{{ number_format($row['cost'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="8">Nenhum consumo de insumo no período.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
