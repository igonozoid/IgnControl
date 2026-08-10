<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100">
            <x-icon name="briefcase" class="w-4 h-4" />
            RH — Funcionários
        </h1>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Funcionários</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">{{ $summary['active'] }} <span class="text-xs font-normal text-gray-400">de {{ $summary['count'] }}</span></p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Folha (salários)</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">R$ {{ number_format($summary['salary_total'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Benefícios</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">R$ {{ number_format($summary['benefits_total'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-neutral-400">Total mensal</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-neutral-100">R$ {{ number_format($summary['payroll_total'], 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar por nome</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Categoria</label>
                <select wire:model.live="staffCategory" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas</option>
                    <option value="domestic_rural">Doméstico/Rural</option>
                    <option value="office">Escritório</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Situação</label>
                <select wire:model.live="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas</option>
                    <option value="active">Ativos</option>
                    <option value="terminated">Ex-funcionários</option>
                </select>
            </div>
            <x-per-page-selector />
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Função</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Categoria</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Admissão</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Salário atual</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Total</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($employees as $row)
                    <tr wire:key="employee-{{ $row->contact->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">
                            {{ $row->contact->name }}
                            @if (($row->contact->employeeProfile?->status ?? 'active') === 'terminated')
                                <span class="ml-1 inline-flex px-1.5 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400">ex-funcionário</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $row->contact->employeeProfile?->job_title ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $row->contact->employeeProfile?->staff_category === 'office' ? 'Escritório' : 'Doméstico/Rural' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $row->contact->employeeProfile?->admission_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400 text-right">R$ {{ number_format($row->currentSalary, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100 text-right font-medium">R$ {{ number_format($row->total, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-xs whitespace-nowrap">
                            <a href="{{ route('hr.profile', $row->contact) }}" wire:navigate class="text-green-600 dark:text-green-400 hover:text-green-800">Ver ficha</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">
                            Nenhum funcionário encontrado. Marque "Funcionário" no cadastro de um contato pra ele aparecer aqui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $employees->links() }}
    </div>
</div>
