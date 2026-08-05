<div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-neutral-700">
        <h2 class="text-xs font-semibold text-gray-900 dark:text-neutral-100">13º salário</h2>
        @if ($this->canWrite)
            <button wire:click="createEntry('thirteenth')" class="flex items-center gap-1.5 px-2.5 py-1 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo registro
            </button>
        @endif
    </div>
    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
        <thead class="bg-gray-50 dark:bg-neutral-700/50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Ano</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Valor pago</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Pagamento</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Observações</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            @forelse ($thirteenthSalaries as $t)
                <tr wire:key="thirteenth-{{ $t->id }}">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $t->year }}</td>
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100 text-right font-medium">{{ $t->amount_paid !== null ? 'R$ '.number_format($t->amount_paid, 2, ',', '.') : '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $t->payment_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $t->notes ?: '—' }}</td>
                    <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                        @if ($this->canWrite)
                            <button wire:click="editEntry('thirteenth', {{ $t->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                            <button wire:click="deleteEntry('thirteenth', {{ $t->id }})" wire:confirm="Excluir este registro de 13º?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum registro de 13º ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
