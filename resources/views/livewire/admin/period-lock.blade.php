<div class="max-w-3xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-4">
        <x-icon name="lock" class="w-4 h-4" />
        Fechamento de Período
    </h1>

    @if (session('status'))
        <div class="flex items-center gap-2 bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-xs rounded-lg px-3 py-2 mb-4">
            <x-icon name="check-circle" class="w-4 h-4" />
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
        <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
            Lançamentos com vencimento até a data abaixo ficam travados: ninguém
            consegue criar, editar ou excluir nada nesse período (nem marcar como
            pago), até você mudar ou remover essa data. Use depois de conferir e
            fechar um mês, pra evitar mexer sem querer em algo já revisado.
        </p>

        @if ($company->locked_through)
            <p class="text-xs text-gray-700 dark:text-neutral-300 mb-4">
                Período fechado atualmente até
                <strong>{{ $company->locked_through->format('d/m/Y') }}</strong>.
            </p>
        @else
            <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
                Nenhum período fechado no momento.
            </p>
        @endif

        <form wire:submit="save" class="flex items-end gap-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Travar até</label>
                <input type="date" wire:model="locked_through"
                    class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('locked_through') <span class="text-red-600 dark:text-red-400 block mt-1">{{ $message }}</span> @enderror
            </div>
            <button type="submit"
                wire:confirm="Confirma a alteração do fechamento de período? Isso muda o que pode ou não ser editado nos lançamentos."
                class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                <x-icon name="check" />
                Salvar
            </button>
        </form>
    </div>
</div>
