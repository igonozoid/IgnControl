@props(['show', 'title', 'close' => null])

{{--
    Painel lateral (slide-over) usado nos formulários de cadastro, no
    lugar do formulário aparecer no meio da página.

    O fundo escurecido de propósito NÃO fecha o painel ao ser clicado —
    só o X ou o botão "Cancelar" do formulário fecham. Isso evita que a
    pessoa perca o que já preencheu com um clique sem querer fora do
    painel.

    `show` é o nome (string) da propriedade booleana do componente
    Livewire que controla a visibilidade (ex.: "showForm").
--}}
<div x-data="{ open: $wire.entangle('{{ $show }}') }" x-show="open" x-cloak style="display: none;" class="fixed inset-0 z-40">
    <div class="fixed inset-0 bg-black/30 dark:bg-black/50"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full sm:w-[460px] max-w-full bg-white dark:bg-neutral-800 shadow-xl overflow-y-auto overflow-x-hidden"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-neutral-700 sticky top-0 bg-white dark:bg-neutral-800 z-10">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-neutral-100">{{ $title }}</h2>
            @if ($close)
                <button wire:click="{{ $close }}" title="Fechar" class="text-gray-400 hover:text-gray-600 dark:text-neutral-500 dark:hover:text-neutral-300">
                    <x-icon name="x-mark" class="w-4 h-4" />
                </button>
            @endif
        </div>
        <div class="p-4 min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
