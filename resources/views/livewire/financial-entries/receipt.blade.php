<div
    class="max-w-2xl mx-auto py-6 px-4"
    x-data="{
        printUrl: @js(route('financial-entries.receipt.print', $entry)),
        openPreview() {
            const params = new URLSearchParams({
                party: this.$refs.party.value,
                entity: this.$refs.entity.value,
                amount: this.$refs.amount.value,
                date: this.$refs.date.value,
                document: this.$refs.document.value,
                words: this.$refs.words.value,
                reference: this.$refs.reference.value,
                notes: this.$refs.notes.value,
                copies: this.$refs.copies.value,
            });
            window.open(this.printUrl + '?' + params.toString(), '_blank');
        },
    }"
>
    <div class="bg-white dark:bg-neutral-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-neutral-100">Emissão de recibo</h1>
            <a href="{{ route('financial-entries.index') }}" class="text-xs text-gray-500 dark:text-neutral-400 hover:text-gray-800 dark:hover:text-neutral-100">← Voltar</a>
        </div>

        <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
            Confira e ajuste os dados abaixo antes de gerar o recibo — nada aqui altera o lançamento original.
        </p>

        <div class="grid grid-cols-1 gap-4 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">{{ $this->partyLabel }}</label>
                <input type="text" x-ref="party" wire:model="partyName" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">{{ $this->entityLabel }}</label>
                <input type="text" x-ref="entity" wire:model="entityName" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor</label>
                    <input type="number" step="0.01" x-ref="amount" wire:model.blur="amount" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                    <input type="date" x-ref="date" wire:model="date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Documento</label>
                    <input type="text" x-ref="document" wire:model="document" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor por extenso</label>
                    <button type="button" wire:click="recalculateWords" class="text-[#4f46e5] hover:underline">Recalcular</button>
                </div>
                <textarea x-ref="words" wire:model="amountWords" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">{{ $amountWords }}</textarea>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Referente a</label>
                <input type="text" x-ref="reference" wire:model="reference" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observação</label>
                <textarea x-ref="notes" wire:model="notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">{{ $notes }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-neutral-700">
                <div class="flex items-center gap-2">
                    <label class="font-medium text-gray-700 dark:text-neutral-300">Número de vias:</label>
                    <select x-ref="copies" wire:model="copies" class="rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>

                <button type="button" @click="openPreview" class="flex items-center gap-1.5 px-3 py-1.5 bg-[#4f46e5] text-white rounded-md font-medium hover:bg-[#4338ca]">
                    <x-icon name="printer" /> Visualizar recibo
                </button>
            </div>
        </div>
    </div>
</div>
