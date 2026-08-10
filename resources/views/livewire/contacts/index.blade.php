<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="users" class="w-4 h-4" />Contatos</h1>
        @if ($this->canWrite)
            <a href="{{ route('contacts.create') }}" wire:navigate class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo contato
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar (nome/documento/e-mail)</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Papel</label>
                <select wire:model.live="filterRole" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todos</option>
                    <option value="supplier">Fornecedor</option>
                    <option value="customer">Cliente</option>
                    <option value="employee">Funcionário</option>
                    <option value="other">Outro</option>
                </select>
            </div>
            <x-per-page-selector />
        </div>
        <label class="mt-3 flex items-center gap-1.5 text-xs text-gray-600 dark:text-neutral-300">
            <input type="checkbox" wire:model.live="onlyNeedsReview" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
            Só pendentes de revisão (cadastrados rápido, direto do lançamento)
        </label>
    </div>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Papéis</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Contato</th>
                    @if ($this->canWrite)
                        <th class="px-4 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($contacts as $contact)
                    <tr wire:key="contact-{{ $contact->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">
                            {{ $contact->name }}
                            @if ($contact->needs_review)
                                <span title="Cadastrado rápido, direto do lançamento — falta completar/revisar" class="ml-1 inline-flex px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">revisar</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            {{ collect([
                                $contact->is_supplier ? 'Fornecedor' : null,
                                $contact->is_customer ? 'Cliente' : null,
                                $contact->is_employee ? 'Funcionário' : null,
                                $contact->is_other ? 'Outro' : null,
                            ])->filter()->implode(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $contact->email ?: $contact->phone ?: '—' }}</td>
                        @if ($this->canWrite)
                            <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                @if ($contact->needs_review)
                                    <button wire:click="markReviewed({{ $contact->id }})" title="Marcar como revisado" class="inline-flex text-amber-600 dark:text-amber-400 hover:text-amber-800"><x-icon name="check-circle" /></button>
                                @endif
                                <a href="{{ route('contacts.edit', $contact) }}" wire:navigate title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></a>
                                <button wire:click="delete({{ $contact->id }})" wire:confirm="Tem certeza que quer excluir este contato?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhum contato cadastrado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $contacts->links() }}
    </div>
</div>
