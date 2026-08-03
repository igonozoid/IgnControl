<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />Contas a Pagar por Fornecedor</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
            &larr; Relatórios
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4 flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-neutral-400">Total em aberto</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">{{ number_format($grandTotal, 2, ',', '.') }}</p>
    </div>

    <div class="space-y-3">
        @forelse ($byContact as $contactName => $group)
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-medium text-gray-900 dark:text-neutral-100">{{ $contactName }}</p>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900 dark:text-neutral-100">{{ number_format($group['total'], 2, ',', '.') }}</p>
                        @if ($group['overdue'] > 0)
                            <p class="text-xs text-[#b42318] dark:text-[#ff453a]">{{ number_format($group['overdue'], 2, ',', '.') }} atrasado</p>
                        @endif
                    </div>
                </div>
                <table class="w-full text-xs">
                    <tbody>
                        @foreach ($group['entries'] as $entry)
                            <tr class="border-t border-gray-50 dark:border-neutral-700">
                                <td class="py-1 text-gray-600 dark:text-neutral-300">{{ $entry->description }}</td>
                                <td class="py-1 text-gray-500 dark:text-neutral-400">{{ $entry->due_date->format('d/m/Y') }}</td>
                                <td @class([
                                    'py-1 text-right',
                                    'text-[#b42318] dark:text-[#ff453a]' => $entry->due_date->isPast(),
                                    'text-[#15803d] dark:text-[#86efac]' => ! $entry->due_date->isPast(),
                                ])>{{ number_format($entry->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 text-gray-400 dark:text-neutral-500">
                Nenhuma conta a pagar em aberto.
            </div>
        @endforelse
    </div>
</div>
