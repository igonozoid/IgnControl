<div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-neutral-700">
        <h2 class="text-xs font-semibold text-gray-900 dark:text-neutral-100">Evolução salarial</h2>
        @if ($this->canWrite)
            <button wire:click="createEntry('salary')" class="flex items-center gap-1.5 px-2.5 py-1 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo registro
            </button>
        @endif
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
        <thead class="bg-gray-50 dark:bg-neutral-700/50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nominal</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Líquido</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Benefícios</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Observações</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            @forelse ($salaryEntries as $entry)
                <tr wire:key="salary-{{ $entry->id }}">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $entry->effective_date?->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400 text-right">{{ $entry->nominal_salary !== null ? 'R$ '.number_format($entry->nominal_salary, 2, ',', '.') : '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100 text-right font-medium">{{ $entry->net_salary !== null ? 'R$ '.number_format($entry->net_salary, 2, ',', '.') : '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400 text-right">{{ $entry->benefits_value !== null ? 'R$ '.number_format($entry->benefits_value, 2, ',', '.') : '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $entry->notes ?: '—' }}</td>
                    <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                        @if ($this->canWrite)
                            <button wire:click="editEntry('salary', {{ $entry->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                            <button wire:click="deleteEntry('salary', {{ $entry->id }})" wire:confirm="Excluir este registro salarial?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum registro salarial ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
