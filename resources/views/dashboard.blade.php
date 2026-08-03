<x-app-layout>
    <div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-4">
            <x-icon name="dashboard" class="w-4 h-4" />
            Dashboard
        </h1>

        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">
                Bem-vindo(a), {{ auth()->user()->name }}. Use o menu ao lado pra acessar Financeiro, Contatos e Relatórios.
            </p>
        </div>
    </div>
</x-app-layout>
