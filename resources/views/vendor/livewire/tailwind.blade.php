{{--
    Override da view de paginação PADRÃO do LIVEWIRE (não a do framework
    Laravel!). Livewire ignora Illuminate\Pagination\AbstractPaginator's
    view — o trait WithPagination força sempre 'livewire::tailwind'
    (namespace 'livewire', registrado pelo próprio pacote), então colocar
    o override em resources/views/vendor/pagination/ (como fizemos antes)
    não tinha efeito nenhum nas telas com WithPagination. O override
    certo é aqui: resources/views/vendor/livewire/tailwind.blade.php.

    Baseado no template original do pacote (mesma estrutura de wire:click
    com nome da página, scroll-into-view, dusk attrs), traduzido pra
    PT-BR.

    IMPORTANTE: o template original do Livewire só renderiza QUALQUER
    coisa (inclusive o "Mostrando X até Y de Z") quando hasPages() é
    true, ou seja, quando existe mais de uma página. Isso fazia o resumo
    de contagem sumir por completo em listagens com poucos registros
    (ex.: Centros de Custo, Lançamentos do mês) — que é justamente onde
    "quantos registros existem" é mais útil de ver. Por isso aqui a
    exibição da contagem foi desacoplada de hasPages(): o texto
    "Mostrando..." aparece sempre que existir pelo menos 1 resultado, e
    só os controles de navegação (setas/números de página) ficam
    condicionados a hasPages().
--}}
@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->total() > 0)
        <nav role="navigation" aria-label="{{ __('Paginação') }}" class="flex items-center justify-between">
            @if ($paginator->hasPages())
                <div class="flex justify-between flex-1 sm:hidden">
                    <span>
                        @if ($paginator->onFirstPage())
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-400">
                                {!! __('« Anterior') !!}
                            </span>
                        @else
                            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700">
                                {!! __('« Anterior') !!}
                            </button>
                        @endif
                    </span>

                    <span>
                        @if ($paginator->hasMorePages())
                            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700">
                                {!! __('Próximo »') !!}
                            </button>
                        @else
                            <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-neutral-500 dark:bg-neutral-800 dark:border-neutral-600">
                                {!! __('Próximo »') !!}
                            </span>
                        @endif
                    </span>
                </div>
            @endif

            <div class="flex-1 flex items-center {{ $paginator->hasPages() ? 'justify-between' : 'justify-start' }}">
                <div>
                    <p class="text-sm text-gray-700 leading-5 dark:text-neutral-400">
                        <span>{!! __('Mostrando') !!}</span>
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('até') !!}</span>
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('de') !!}</span>
                        <span class="font-medium">{{ $paginator->total() }}</span>
                        <span>{!! __('resultados') !!}</span>
                    </p>
                </div>

                @if ($paginator->hasPages())
                    <div class="hidden sm:block">
                        <span class="relative z-0 inline-flex rtl:flex-row-reverse rounded-md shadow-sm">
                            <span>
                                {{-- Página anterior --}}
                                @if ($paginator->onFirstPage())
                                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5 dark:bg-neutral-800 dark:border-neutral-600" aria-hidden="true">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </span>
                                @else
                                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-neutral-800 dark:border-neutral-600 dark:hover:bg-neutral-700" aria-label="{{ __('pagination.previous') }}">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                @endif
                            </span>

                            {{-- Números de página --}}
                            @foreach ($elements as $element)
                                {{-- "Três pontinhos" --}}
                                @if (is_string($element))
                                    <span aria-disabled="true">
                                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-300">{{ $element }}</span>
                                    </span>
                                @endif

                                {{-- Links de página --}}
                                @if (is_array($element))
                                    @foreach ($element as $page => $url)
                                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                            @if ($page == $paginator->currentPage())
                                                <span aria-current="page">
                                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-green-700 bg-green-50 border border-green-300 cursor-default leading-5 dark:text-green-400 dark:bg-green-500/10 dark:border-green-700">{{ $page }}</span>
                                                </span>
                                            @else
                                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-400 dark:hover:bg-neutral-700" aria-label="{{ __('Ir para a página :page', ['page' => $page]) }}">
                                                    {{ $page }}
                                                </button>
                                            @endif
                                        </span>
                                    @endforeach
                                @endif
                            @endforeach

                            <span>
                                {{-- Próxima página --}}
                                @if ($paginator->hasMorePages())
                                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-neutral-800 dark:border-neutral-600 dark:hover:bg-neutral-700" aria-label="{{ __('pagination.next') }}">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                @else
                                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                        <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5 dark:bg-neutral-800 dark:border-neutral-600" aria-hidden="true">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </span>
                                @endif
                            </span>
                        </span>
                    </div>
                @endif
            </div>
        </nav>
    @endif
</div>
