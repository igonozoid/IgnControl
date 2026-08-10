<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="list" class="w-4 h-4" />Pedidos de Venda</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo pedido
            </button>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas</option>
                    @foreach (\App\Models\SalesOrder::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Tipo</label>
                <select wire:model.live="filterSaleType" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach (\App\Models\SalesOrder::SALE_TYPES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-per-page-selector />
        </div>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar pedido' : 'Novo pedido' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            @if ($formError)
                <div class="rounded-md bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-amber-700 dark:text-amber-400">
                    {{ $formError }}
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Contato (opcional)</label>
                    <select wire:model="contact_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="">— nenhum —</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo</label>
                    <select wire:model="sale_type" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @foreach (\App\Models\SalesOrder::SALE_TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                    <input type="date" wire:model="sale_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('sale_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Vencimento (opcional)</label>
                    <input type="date" wire:model="due_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Status</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="draft">Rascunho</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="settled">Liquidado</option>
                    </select>
                    <p class="text-gray-400 dark:text-neutral-500 mt-1">Confirmado/Liquidado já movimenta estoque.</p>
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-700 dark:text-neutral-300">Itens</p>
                    <button type="button" wire:click="addItemRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
                </div>
                @foreach ($itemRows as $i => $row)
                    <div wire:key="item-row-{{ $i }}" class="grid grid-cols-12 gap-1.5 items-start mb-2">
                        <select wire:model.live="itemRows.{{ $i }}.product_id" class="col-span-4 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">Produto</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.001" min="0" placeholder="Qtd." wire:model.live="itemRows.{{ $i }}.quantity" class="col-span-2 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="number" step="0.01" min="0" placeholder="Preço" wire:model.live="itemRows.{{ $i }}.unit_price" class="col-span-2 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="number" step="0.01" min="0" placeholder="Desc." wire:model.live="itemRows.{{ $i }}.discount_amount" class="col-span-2 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="number" step="0.001" min="0" max="100" placeholder="Tax %" wire:model.live="itemRows.{{ $i }}.tax_rate_percent" class="col-span-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <button type="button" wire:click="removeItemRow({{ $i }})" title="Remover" class="col-span-1 shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                    </div>
                @endforeach
                @if (empty($itemRows))
                    <p class="text-gray-400 dark:text-neutral-500">Nenhum item adicionado.</p>
                @endif
            </div>

            <div class="bg-gray-50 dark:bg-neutral-700/40 rounded-md p-3 space-y-1">
                <div class="flex justify-between"><span class="text-gray-500 dark:text-neutral-400">Subtotal</span><span>{{ number_format($this->previewTotals['subtotal'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 dark:text-neutral-400">Desconto</span><span>-{{ number_format($this->previewTotals['discount'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 dark:text-neutral-400">Impostos</span><span>+{{ number_format($this->previewTotals['tax'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between font-semibold text-gray-900 dark:text-neutral-100 border-t border-gray-200 dark:border-neutral-600 pt-1"><span>Total</span><span>{{ number_format($this->previewTotals['total'], 2, ',', '.') }}</span></div>
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3 space-y-3">
                <label class="flex items-center gap-1.5 text-gray-700 dark:text-neutral-300"><input type="checkbox" wire:model.live="generate_financial_entry" class="rounded dark:bg-neutral-700 dark:border-neutral-600"> Gerar lançamento financeiro (só pra tipo "Venda")</label>

                @if ($generate_financial_entry && $sale_type === 'sale')
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-neutral-300">Conta que recebe</label>
                            <select wire:model="financial_account_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <option value="">— selecione —</option>
                                @foreach ($financialAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('financial_account_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-neutral-300">Categoria (opcional)</label>
                            <select wire:model="category_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                                <option value="">— nenhuma —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Centro de custo (opcional)</label>
                        <select wire:model="cost_center_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <option value="">— nenhum —</option>
                            @foreach ($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações (opcional)</label>
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Salvar
                </button>
                <button type="button" wire:click="cancel" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Contato</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Tipo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Status</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Total</th>
                    @if ($this->canWrite)
                        <th class="px-4 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($orders as $order)
                    <tr wire:key="sales-order-{{ $order->id }}">
                        <td class="px-4 py-2 text-xs text-gray-700 dark:text-neutral-300 whitespace-nowrap">{{ $order->sale_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $order->contact?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ \App\Models\SalesOrder::SALE_TYPES[$order->sale_type] ?? $order->sale_type }}</td>
                        <td class="px-4 py-2 text-xs">
                            <span @class([
                                'px-1.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' => $order->status === 'draft',
                                'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $order->status === 'confirmed',
                                'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' => $order->status === 'settled',
                                'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $order->status === 'cancelled',
                            ])>{{ \App\Models\SalesOrder::STATUSES[$order->status] ?? $order->status }}</span>
                        </td>
                        <td class="px-4 py-2 text-right text-xs text-gray-900 dark:text-neutral-100 whitespace-nowrap">{{ number_format($order->total_amount, 2, ',', '.') }} {{ $order->currency_code }}</td>
                        @if ($this->canWrite)
                            <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                @if ($order->status !== 'cancelled')
                                    <button wire:click="edit({{ $order->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                @endif
                                @if ($order->status === 'draft')
                                    <button wire:click="delete({{ $order->id }})" wire:confirm="Tem certeza que quer excluir este pedido?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                                @else
                                    <button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Cancelar este pedido? Isso estorna o estoque e marca o lançamento financeiro como cancelado." title="Cancelar pedido" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="x-circle" /></button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum pedido cadastrado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>
