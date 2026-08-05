<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{--
            Aplica o tema (claro/escuro) ANTES da página desenhar, senão
            dá aquele "flash" de tela clara por uma fração de segundo
            mesmo com o usuário preferindo escuro. Padrão é claro; só
            fica escuro se o usuário escolher (guardado no navegador).
        --}}
        <script>
            function applyTheme() {
                document.documentElement.classList.toggle('dark', localStorage.theme === 'dark');
            }
            applyTheme();

            // wire:navigate troca o conteúdo da página via fetch (sem
            // reload completo), então esse script não roda de novo — só
            // reaplicamos a classe "dark" no <html> quando o Livewire
            // termina uma navegação, senão o tema volta pro claro ao
            // trocar de página mesmo com a preferência salva.
            document.addEventListener('livewire:navigated', applyTheme);
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{--
            h-screen + overflow-hidden aqui é o que trava a altura da tela
            toda no viewport — sem isso, a página cresce conforme o
            conteúdo e o menu lateral (que troca pra "static" no flow
            normal em telas grandes) esticava junto até a altura do
            conteúdo, em vez de ficar travado na altura da tela e rolar
            por conta própria.
        --}}
        <div class="h-screen overflow-hidden flex bg-gray-100 dark:bg-neutral-900" x-data="{ sidebarOpen: false }">
            <livewire:layout.navigation />

            <!-- Conteúdo principal — rola por conta própria, independente do menu -->
            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
                <!-- Overlay pra fechar o menu no mobile -->
                <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                    class="fixed inset-0 bg-black/30 z-30 lg:hidden"></div>

                <!-- Botão de abrir o menu no mobile -->
                <div class="lg:hidden flex items-center justify-between bg-white dark:bg-neutral-800 border-b border-gray-100 dark:border-neutral-700 px-4 py-2">
                    <button @click="sidebarOpen = true" class="text-gray-500 dark:text-neutral-400">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <x-application-logo class="block h-7 w-auto fill-current text-gray-800 dark:text-neutral-100" />
                    <div></div>
                </div>

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white dark:bg-neutral-800 shadow-sm">
                        <div class="py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
