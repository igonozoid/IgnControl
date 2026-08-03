<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-4">
        <x-icon name="dashboard" class="w-4 h-4" />
        Dashboard
    </h1>

    @if ($canReview && ($needsReviewCount ?? 0) > 0)
        <a href="{{ route('contacts.index') }}" wire:navigate class="flex items-center gap-2 bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs rounded-lg px-3 py-2 mb-4 hover:opacity-80">
            <x-icon name="lock" class="w-4 h-4" />
            {{ $needsReviewCount }} {{ $needsReviewCount === 1 ? 'cadastro pendente de revisão' : 'cadastros pendentes de revisão' }} (criados rápido, direto de um lançamento).
        </a>
    @endif

    @if (! $canSeeFinancial && ! $canSeeAgenda)
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-neutral-400">
                Bem-vindo(a), {{ auth()->user()->name }}. Use o menu ao lado pra acessar o que você tem liberado.
            </p>
        </div>
    @endif

    @if ($canSeeFinancial)
        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Saldo em caixa</p>
                <p @class([
                    'text-lg font-semibold mt-1',
                    'text-[#15803d] dark:text-[#86efac]' => $cashBalance >= 0,
                    'text-[#b42318] dark:text-[#ff453a]' => $cashBalance < 0,
                ])>{{ number_format($cashBalance, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">A receber em aberto</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100 mt-1">{{ number_format($receivablesTotal, 2, ',', '.') }}</p>
                @if ($receivablesOverdue > 0)
                    <p class="text-xs text-[#b42318] dark:text-[#ff453a] mt-0.5">{{ number_format($receivablesOverdue, 2, ',', '.') }} atrasado</p>
                @endif
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">A pagar em aberto</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100 mt-1">{{ number_format($payablesTotal, 2, ',', '.') }}</p>
                @if ($payablesOverdue > 0)
                    <p class="text-xs text-[#b42318] dark:text-[#ff453a] mt-0.5">{{ number_format($payablesOverdue, 2, ',', '.') }} atrasado</p>
                @endif
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Resultado do mês</p>
                @php $monthNet = $monthIncome - $monthExpense; @endphp
                <p @class([
                    'text-lg font-semibold mt-1',
                    'text-[#15803d] dark:text-[#86efac]' => $monthNet >= 0,
                    'text-[#b42318] dark:text-[#ff453a]' => $monthNet < 0,
                ])>{{ number_format($monthNet, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            {{-- Tendência 6 meses --}}
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 lg:col-span-2">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-3">Receitas x despesas — últimos 6 meses</p>
                <div class="flex items-end justify-between gap-2 h-32">
                    @foreach ($months as $month)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full flex items-end justify-center gap-0.5 h-24">
                                <div class="w-1/2 bg-green-500/80 dark:bg-green-500/60 rounded-t" style="height: {{ $maxMonthValue > 0 ? max(2, ($month['income'] / $maxMonthValue) * 100) : 2 }}%" title="Receita: {{ number_format($month['income'], 2, ',', '.') }}"></div>
                                <div class="w-1/2 bg-red-500/70 dark:bg-red-500/60 rounded-t" style="height: {{ $maxMonthValue > 0 ? max(2, ($month['expense'] / $maxMonthValue) * 100) : 2 }}%" title="Despesa: {{ number_format($month['expense'], 2, ',', '.') }}"></div>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-neutral-500">{{ $month['label'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-neutral-400">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-500/80"></span> Receita</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-500/70"></span> Despesa</span>
                </div>
            </div>

            {{-- Próximos vencimentos --}}
            <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-3">Próximos vencimentos</p>
                <div class="space-y-2">
                    @forelse ($upcomingEntries as $entry)
                        @php $overdue = $entry->due_date->isPast(); @endphp
                        <div class="flex items-center justify-between text-xs">
                            <div class="min-w-0">
                                <p class="text-gray-700 dark:text-neutral-200 truncate">{{ $entry->description ?: $entry->contact?->name ?: 'Lançamento' }}</p>
                                <p @class(['text-[#b42318] dark:text-[#ff453a]' => $overdue, 'text-gray-400 dark:text-neutral-500' => ! $overdue])>{{ $entry->due_date->format('d/m/Y') }}</p>
                            </div>
                            <span @class([
                                'font-medium whitespace-nowrap ml-2',
                                'text-[#15803d] dark:text-[#86efac]' => $entry->type === 'income',
                                'text-[#b42318] dark:text-[#ff453a]' => $entry->type === 'expense',
                            ])>{{ number_format($entry->amount, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 dark:text-neutral-500">Nada vencendo nos próximos 14 dias.</p>
                    @endforelse
                </div>
                <a href="{{ route('financial-entries.index') }}" wire:navigate class="block text-xs text-green-600 dark:text-green-400 hover:underline mt-3">Ver lançamentos &rarr;</a>
            </div>
        </div>
    @endif

    @if ($canSeeAgenda)
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase mb-3">Tarefas pendentes</p>
            <div class="space-y-2">
                @forelse ($upcomingTasks as $task)
                    <div class="flex items-center justify-between text-xs">
                        <p class="text-gray-700 dark:text-neutral-200">{{ $task->title }}</p>
                        <p @class([
                            'whitespace-nowrap ml-2',
                            'text-[#b42318] dark:text-[#ff453a]' => $task->isOverdue(),
                            'text-gray-400 dark:text-neutral-500' => ! $task->isOverdue(),
                        ])>{{ $task->due_date?->format('d/m/Y') ?? 'sem data' }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 dark:text-neutral-500">Nenhuma tarefa pendente.</p>
                @endforelse
            </div>
            <a href="{{ route('tasks.index') }}" wire:navigate class="block text-xs text-green-600 dark:text-green-400 hover:underline mt-3">Ver agenda &rarr;</a>
        </div>
    @endif
</div>
