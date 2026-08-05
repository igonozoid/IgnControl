<div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-neutral-700">
        <h2 class="text-xs font-semibold text-gray-900 dark:text-neutral-100">Benefícios recorrentes</h2>
        @if ($this->canWrite)
            <button wire:click="createEntry('benefit')" class="flex items-center gap-1.5 px-2.5 py-1 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo benefício
            </button>
        @endif
    </div>
    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
        <thead class="bg-gray-50 dark:bg-neutral-700/50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Valor mensal</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            @forelse ($benefitEntries as $b)
                <tr wire:key="benefit-{{ $b->id }}">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $b->name }}</td>
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100 text-right font-medium">R$ {{ number_format($b->monthly_value, 2, ',', '.') }}</td>
                    <td class="px-4 py-2 text-xs">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $b->active ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400' }}">
                            {{ $b->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                        @if ($this->canWrite)
                            <button wire:click="editEntry('benefit', {{ $b->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                            <button wire:click="deleteEntry('benefit', {{ $b->id }})" wire:confirm="Excluir este benefício?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum benefício cadastrado ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
