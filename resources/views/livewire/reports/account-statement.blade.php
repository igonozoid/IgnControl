<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />Extrato por Conta Financeira</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-green-600 dark:text-green-400 hover:underline">
            &larr; Relatórios
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">Conta</label>
                <select wire:model.live="accountId" class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
                    @foreach ($accounts as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
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

    @if (! $account)
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 text-gray-400 dark:text-neutral-500">
            Nenhuma conta financeira cadastrada ainda.
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-neutral-400">Saldo inicial ({{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }})</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">{{ number_format($openingBalance, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-neutral-400">Saldo final ({{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }})</p>
                <p @class([
                    'text-lg font-semibold',
                    'text-[#15803d] dark:text-[#86efac]' => $closingBalance >= 0,
                    'text-[#b42318] dark:text-[#ff453a]' => $closingBalance < 0,
                ])>{{ number_format($closingBalance, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Data</th>
                        <th class="text-left px-4 py-2">Descrição</th>
                        <th class="text-left px-4 py-2">Contra-parte</th>
                        <th class="text-right px-4 py-2">Valor</th>
                        <th class="text-right px-4 py-2">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $entry = $row['entry']; @endphp
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-600 dark:text-neutral-300">{{ $entry->paid_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $entry->description ?: '—' }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">
                                @if ($entry->type === 'transfer')
                                    Transf.: {{ $entry->financialAccount?->name }} → {{ $entry->destinationAccount?->name }}
                                @else
                                    {{ $entry->contact?->name ?? $entry->category?->name ?? '—' }}
                                @endif
                            </td>
                            <td @class([
                                'px-4 py-1.5 text-right',
                                'text-[#15803d] dark:text-[#86efac]' => $row['signed'] >= 0,
                                'text-[#b42318] dark:text-[#ff453a]' => $row['signed'] < 0,
                            ])>{{ number_format($row['signed'], 2, ',', '.') }}</td>
                            <td class="px-4 py-1.5 text-right font-medium text-gray-900 dark:text-neutral-100">{{ number_format($row['balance'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="5">Nenhum movimento pago no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
