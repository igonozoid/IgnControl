<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100">
            <x-icon name="document" class="w-4 h-4" />
            Auditoria
        </h1>
        <a href="{{ $this->printUrl }}" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md text-xs font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
            <x-icon name="printer" />
            Imprimir com estes filtros
        </a>
    </div>

    <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
        Registro de criação, edição, exclusão e acessos sensíveis (ex.: visualizar
        ou copiar uma senha do cofre de credenciais) — sempre com usuário, data e hora.
    </p>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">De</label>
                <input type="date" wire:model.live="dateFrom" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Até</label>
                <input type="date" wire:model.live="dateTo" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Usuário</label>
                <select wire:model.live="userId" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Ação</label>
                <select wire:model.live="action" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas</option>
                    @foreach (['created', 'updated', 'deleted', 'viewed', 'copied'] as $a)
                        <option value="{{ $a }}">{{ \App\Models\AuditLog::actionLabel($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Tipo de registro</label>
                <select wire:model.live="model" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    @foreach ($models as $m)
                        <option value="{{ $m }}">{{ \App\Models\AuditLog::modelLabel($m) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if ($dateFrom || $dateTo || $userId || $action || $model)
            <button type="button" wire:click="clearFilters" class="mt-3 text-xs text-gray-500 dark:text-neutral-400 underline hover:text-gray-700 dark:hover:text-neutral-200">Limpar filtros</button>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Data/hora</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Usuário</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Ação</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Registro</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Detalhe</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($logs as $log)
                    <tr wire:key="audit-{{ $log->id }}">
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                {{ match ($log->action) {
                                    'created' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                    'updated' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    'deleted' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                    'copied' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-neutral-700 dark:text-neutral-300',
                                } }}">
                                {{ \App\Models\AuditLog::actionLabel($log->action) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            {{ \App\Models\AuditLog::modelLabel($log->auditable_type) }} #{{ $log->auditable_id }}
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            @if ($log->action === 'updated' && $log->new_values)
                                {{ collect($log->new_values)->keys()->implode(', ') }}
                            @elseif (in_array($log->action, ['viewed', 'copied']))
                                <span class="italic">valor não registrado (apenas o acesso)</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum registro de auditoria encontrado com esses filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
