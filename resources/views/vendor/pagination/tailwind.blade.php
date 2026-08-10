{{--
    View de paginação padrão do Laravel, traduzida pra PT-BR e com
    variantes dark: pra bater com o resto do app (a view original do
    framework não tem nenhuma das duas coisas — "Showing X to Y of Z
    results" vem hardcoded em inglês, sem passar por __()).

    Usada automaticamente por QUALQUER `{{ $paginator->links() }}` no
    sistema, sem precisar tocar em cada tela.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Paginação') }}" class="flex items-center justify-between text-xs">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md cursor-default">{{ __('« Anterior') }}</span>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-700">{{ __('« Anterior') }}</button>
            @endif

            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-3 py-1.5 ms-2 text-xs font-medium text-gray-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-700">{{ __('Próximo »') }}</button>
            @else
                <span class="relative inline-flex items-center px-3 py-1.5 ms-2 text-xs font-medium text-gray-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md cursor-default">{{ __('Próximo »') }}</span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-gray-600 dark:text-neutral-400">
                    {{ __('Mostrando') }}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {{ __('até') }}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        <span class="font-medium">{{ $paginator->count() }}</span>
                    @endif
                    {{ __('de') }}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {{ __('resultados') }}
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rtl:flex-row-reverse shadow-sm rounded-md">
                    {{-- Anterior --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-2 py-1.5 -me-px text-xs font-medium text-gray-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-s-md cursor-default" aria-hidden="true">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @else
                        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-2 py-1.5 -me-px text-xs font-medium text-gray-500 dark:text-neutral-400 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-s-md hover:bg-gray-50 dark:hover:bg-neutral-700 focus:z-10" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                        </button>
                    @endif

                    {{-- Elementos --}}
                    @foreach ($elements as $element)
                        {{-- "Três pontinhos" --}}
                        @if (is_string($element))
                            <span aria-disabled="true"><span class="relative inline-flex items-center px-3 py-1.5 -me-px text-xs font-medium text-gray-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 cursor-default">{{ $element }}</span></span>
                        @endif

                        {{-- Array de links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page"><span class="relative inline-flex items-center px-3 py-1.5 -me-px text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/10 border border-green-300 dark:border-green-700 cursor-default">{{ $page }}</span></span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" class="relative inline-flex items-center px-3 py-1.5 -me-px text-xs font-medium text-gray-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 hover:bg-gray-50 dark:hover:bg-neutral-700 focus:z-10" aria-label="{{ __('Ir para a página :page', ['page' => $page]) }}">{{ $page }}</button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Próximo --}}
                    @if ($paginator->hasMorePages())
                        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-2 py-1.5 -ms-px text-xs font-medium text-gray-500 dark:text-neutral-400 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-e-md hover:bg-gray-50 dark:hover:bg-neutral-700 focus:z-10" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-2 py-1.5 -ms-px text-xs font-medium text-gray-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-e-md cursor-default" aria-hidden="true">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
