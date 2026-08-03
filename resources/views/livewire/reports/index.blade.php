<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-4"><x-icon name="chart" class="w-4 h-4" />Relatórios</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a href="{{ route('reports.dre') }}" wire:navigate
            class="block bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 hover:ring-1 hover:ring-green-300 dark:hover:ring-green-500">
            <p class="font-medium text-gray-900 dark:text-neutral-100">DRE</p>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Demonstração de resultado por categoria, num período.</p>
        </a>

        <a href="{{ route('reports.cash-flow') }}" wire:navigate
            class="block bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 hover:ring-1 hover:ring-green-300 dark:hover:ring-green-500">
            <p class="font-medium text-gray-900 dark:text-neutral-100">Fluxo de Caixa</p>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Saldo inicial, movimentos pagos dia a dia e saldo final.</p>
        </a>

        <a href="{{ route('reports.payables') }}" wire:navigate
            class="block bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 hover:ring-1 hover:ring-green-300 dark:hover:ring-green-500">
            <p class="font-medium text-gray-900 dark:text-neutral-100">Contas a Pagar por Fornecedor</p>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Despesas em aberto agrupadas por fornecedor.</p>
        </a>

        <a href="{{ route('reports.receivables') }}" wire:navigate
            class="block bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 hover:ring-1 hover:ring-green-300 dark:hover:ring-green-500">
            <p class="font-medium text-gray-900 dark:text-neutral-100">Contas a Receber por Cliente</p>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Receitas em aberto agrupadas por cliente.</p>
        </a>

        <a href="{{ route('reports.cost-centers') }}" wire:navigate
            class="block bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 hover:ring-1 hover:ring-green-300 dark:hover:ring-green-500 sm:col-span-2">
            <p class="font-medium text-gray-900 dark:text-neutral-100">Despesas/Receitas por Centro de Custo</p>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Totais por centro de custo, num período.</p>
        </a>
    </div>
</div>
