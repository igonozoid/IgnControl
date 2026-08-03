<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Contas Financeiras</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                + Nova conta
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium mb-4">{{ $editingId ? 'Editar conta' : 'Nova conta' }}</h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="cash">Caixa</option>
                            <option value="bank">Banco</option>
                        </select>
                        @error('type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Moeda</label>
                        <select wire:model="currency_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} — {{ $currency->name }}</option>
                            @endforeach
                        </select>
                        @error('currency_code') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Saldo inicial</label>
                    <input type="number" step="0.01" wire:model="opening_balance" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('opening_balance') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        Salvar
                    </button>
                    <button type="button" wire:click="cancel" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Moeda</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo atual</th>
                    @if ($this->canWrite)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($accounts as $account)
                    <tr wire:key="account-{{ $account->id }}">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $account->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $account->type === 'cash' ? 'Caixa' : 'Banco' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $account->currency_code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format((float) $account->currentBalance(), 2, ',', '.') }}</td>
                        @if ($this->canWrite)
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <button wire:click="edit({{ $account->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                                <button wire:click="delete({{ $account->id }})" wire:confirm="Tem certeza que quer excluir esta conta?" class="text-red-600 hover:text-red-900">Excluir</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma conta cadastrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $accounts->links() }}
    </div>
</div>
