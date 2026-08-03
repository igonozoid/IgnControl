<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="chart" class="w-4 h-4" />Relatórios Cadastrais</h1>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-green-600 dark:text-green-400 hover:underline">
            &larr; Relatórios
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 mb-4">
        <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1">Cadastro</label>
        <select wire:model.live="type" class="rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 text-xs">
            <option value="accounts">Contas Financeiras</option>
            <option value="categories">Categorias</option>
            <option value="cost-centers">Centros de Custo</option>
            <option value="contacts">Contatos</option>
        </select>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <table class="w-full text-xs">
            @if ($type === 'accounts')
                <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Nome</th>
                        <th class="text-left px-4 py-2">Tipo</th>
                        <th class="text-left px-4 py-2">Moeda</th>
                        <th class="text-left px-4 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $row->name }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->type }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->currency_code }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->is_active ? 'Ativa' : 'Inativa' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="4">Nada cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            @elseif ($type === 'categories')
                <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Nome</th>
                        <th class="text-left px-4 py-2">Tipo</th>
                        <th class="text-left px-4 py-2">Categoria pai</th>
                        <th class="text-left px-4 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $row->name }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->type === 'income' ? 'Receita' : 'Despesa' }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->parent?->name ?? '—' }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->is_active ? 'Ativa' : 'Inativa' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="4">Nada cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            @elseif ($type === 'cost-centers')
                <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Nome</th>
                        <th class="text-left px-4 py-2">Código</th>
                        <th class="text-left px-4 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $row->name }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->code ?? '—' }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->is_active ? 'Ativo' : 'Inativo' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="3">Nada cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            @else
                <thead class="bg-gray-50 dark:bg-neutral-700/50 text-xs text-gray-500 dark:text-neutral-400 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Nome</th>
                        <th class="text-left px-4 py-2">Papéis</th>
                        <th class="text-left px-4 py-2">Contato</th>
                        <th class="text-left px-4 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-gray-50 dark:border-neutral-700">
                            <td class="px-4 py-1.5 text-gray-700 dark:text-neutral-200">{{ $row->name }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">
                                {{ collect([
                                    $row->is_supplier ? 'Fornecedor' : null,
                                    $row->is_customer ? 'Cliente' : null,
                                    $row->is_employee ? 'Funcionário' : null,
                                    $row->is_other ? 'Outro' : null,
                                ])->filter()->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->email ?: $row->phone ?: '—' }}</td>
                            <td class="px-4 py-1.5 text-gray-500 dark:text-neutral-400">{{ $row->is_active ? 'Ativo' : 'Inativo' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-3 text-gray-400 dark:text-neutral-500" colspan="4">Nada cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            @endif
        </table>
    </div>
</div>
