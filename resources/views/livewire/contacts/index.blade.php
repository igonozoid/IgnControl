<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="users" class="w-4 h-4" />Contatos</h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Novo contato
            </button>
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
        </div>
        <label class="mt-3 flex items-center gap-1.5 text-xs text-gray-600 dark:text-neutral-300">
            <input type="checkbox" wire:model.live="onlyNeedsReview" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
            Só pendentes de revisão (cadastrados rápido, direto do lançamento)
        </label>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar contato' : 'Novo contato' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Documento (CPF/CNPJ)</label>
                    <input type="text" wire:model.live="document" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('document') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if ($this->isCnpjDocument)
                            <button type="button" wire:click="buscarCnpj" wire:loading.attr="disabled" wire:target="buscarCnpj"
                                class="flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 hover:bg-gray-200 dark:hover:bg-neutral-600 disabled:opacity-50">
                                <span wire:loading.remove wire:target="buscarCnpj">Busca Básica</span>
                                <span wire:loading wire:target="buscarCnpj">Consultando...</span>
                            </button>
                        @endif
                        @if ($this->creditSearchLinks->isNotEmpty())
                            @if ($this->creditSearchLinks->count() === 1)
                                <a href="{{ $this->creditSearchLinks->first()->url }}" target="_blank" rel="noopener"
                                    class="flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 hover:bg-gray-200 dark:hover:bg-neutral-600">
                                    Busca Avançada — {{ $this->creditSearchLinks->first()->title }}
                                    <x-icon name="arrow-top-right-on-square" class="w-3 h-3" />
                                </a>
                            @else
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @click="open = ! open"
                                        class="flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 hover:bg-gray-200 dark:hover:bg-neutral-600">
                                        Busca Avançada
                                        <x-icon name="arrow-top-right-on-square" class="w-3 h-3" />
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak
                                        class="absolute z-10 mt-1 w-48 rounded-md bg-white dark:bg-neutral-800 shadow-lg border border-gray-100 dark:border-neutral-700 py-1">
                                        @foreach ($this->creditSearchLinks as $link)
                                            <a href="{{ $link->url }}" target="_blank" rel="noopener"
                                                class="block px-3 py-1.5 text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700">{{ $link->title }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    @if ($this->creditSearchLinks->isEmpty())
                        <p class="text-gray-400 dark:text-neutral-500 mt-1">
                            Cadastre um link de portal de consulta (SPC/Serasa) no
                            <a href="{{ route('admin.credentials.index') }}" class="underline">cofre de credenciais</a>
                            para habilitar esse atalho.
                        </p>
                    @endif
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">RG / Inscrição Estadual</label>
                    <input type="text" wire:model="secondary_document" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('secondary_document') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Data de nascimento / fundação</label>
                <input type="date" wire:model="birth_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('birth_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                <p class="text-gray-500 dark:text-neutral-400 mt-1">Se preenchido, aparece como lembrete anual na Agenda.</p>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">E-mail</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('email') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Telefone</label>
                    <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('phone') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Bairro</label>
                    <input type="text" wire:model="district" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('district') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Endereço</label>
                    <input type="text" wire:model="address_line1" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('address_line1') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Complemento</label>
                    <input type="text" wire:model="address_line2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('address_line2') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Cidade</label>
                    <input type="text" wire:model="city" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('city') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Estado</label>
                    <input type="text" wire:model="state" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('state') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">CEP</label>
                    <input type="text" wire:model="postal_code" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('postal_code') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">País</label>
                    <input type="text" wire:model="country" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('country') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Papéis</label>
                <div class="flex flex-wrap gap-3 text-gray-700 dark:text-neutral-300">
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="is_supplier" class="dark:bg-neutral-700 dark:border-neutral-600"> Fornecedor</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="is_customer" class="dark:bg-neutral-700 dark:border-neutral-600"> Cliente</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="is_employee" class="dark:bg-neutral-700 dark:border-neutral-600"> Funcionário</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="is_other" class="dark:bg-neutral-700 dark:border-neutral-600"> Outro</label>
                </div>
                @if ($is_employee && $editingId)
                    <a href="{{ route('hr.profile', $editingId) }}" wire:navigate class="inline-flex items-center gap-1 mt-2 text-green-600 dark:text-green-400 hover:text-green-800">
                        <x-icon name="briefcase" class="w-3.5 h-3.5" />
                        Ver ficha de RH
                    </a>
                @endif
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                <p class="font-semibold text-gray-700 dark:text-neutral-300 mb-2">Crédito</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Frequência de compra</label>
                        <input type="text" wire:model="purchase_frequency" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Classificação</label>
                        <input type="text" wire:model="classification" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Limite de crédito</label>
                        <input type="number" step="0.01" min="0" wire:model="credit_limit" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @error('credit_limit') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome da mãe</label>
                        <input type="text" wire:model="mother_name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <p class="text-gray-500 dark:text-neutral-400 mt-1">Usado em consulta de crédito de pessoa física.</p>
                    </div>
                </div>

                <label class="mt-3 flex items-center gap-1.5 text-gray-700 dark:text-neutral-300">
                    <input type="checkbox" wire:model.live="credit_checked" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
                    Crédito consultado
                </label>
                @if ($credit_checked)
                    <div class="mt-2">
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Data da consulta</label>
                        <input type="date" wire:model="credit_check_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @error('credit_check_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                @endif

                <label class="mt-3 flex items-center gap-1.5 text-gray-700 dark:text-neutral-300">
                    <input type="checkbox" wire:model.live="has_credit_issue" class="rounded dark:bg-neutral-700 dark:border-neutral-600">
                    Possui pendência de crédito
                </label>
                @if ($has_credit_issue)
                    <div class="mt-2">
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Local da pendência</label>
                        <input type="text" wire:model="credit_issue_location" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @error('credit_issue_location') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if (! empty($existingDocuments))
                    <div class="mt-3">
                        <p class="font-medium text-gray-700 dark:text-neutral-300 mb-1">Documentos anexados</p>
                        <ul class="space-y-1">
                            @foreach ($existingDocuments as $doc)
                                <li class="flex items-center gap-1.5 text-gray-600 dark:text-neutral-300">
                                    <x-icon name="document" class="w-3.5 h-3.5" />
                                    <a href="{{ route('contacts.documents.download', $doc['id']) }}" class="underline hover:text-green-700 dark:hover:text-green-400">{{ $doc['original_name'] }}</a>
                                    <span class="text-gray-400 dark:text-neutral-500">— {{ $doc['created_at'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-700 dark:text-neutral-300">Referências comerciais</p>
                    <button type="button" wire:click="addCommercialReferenceRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
                </div>
                @foreach ($commercialReferenceRows as $i => $row)
                    <div wire:key="commercial-ref-{{ $i }}" class="flex gap-2 items-start mb-2">
                        <input type="text" placeholder="Nome" wire:model="commercialReferenceRows.{{ $i }}.name" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="text" placeholder="Telefone" wire:model="commercialReferenceRows.{{ $i }}.phone" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <button type="button" wire:click="removeCommercialReferenceRow({{ $i }})" title="Remover" class="shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                    </div>
                @endforeach
                @if (empty($commercialReferenceRows))
                    <p class="text-gray-400 dark:text-neutral-500">Nenhuma referência adicionada.</p>
                @endif
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-700 dark:text-neutral-300">Referências bancárias</p>
                    <button type="button" wire:click="addBankReferenceRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
                </div>
                @foreach ($bankReferenceRows as $i => $row)
                    <div wire:key="bank-ref-{{ $i }}" class="grid grid-cols-4 gap-2 items-start mb-2">
                        <input type="text" placeholder="Banco" wire:model="bankReferenceRows.{{ $i }}.bank" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="text" placeholder="Agência" wire:model="bankReferenceRows.{{ $i }}.agency" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="text" placeholder="Conta" wire:model="bankReferenceRows.{{ $i }}.account" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <div class="flex gap-2">
                            <input type="text" placeholder="Telefone" wire:model="bankReferenceRows.{{ $i }}.phone" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <button type="button" wire:click="removeBankReferenceRow({{ $i }})" title="Remover" class="shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                        </div>
                    </div>
                @endforeach
                @if (empty($bankReferenceRows))
                    <p class="text-gray-400 dark:text-neutral-500">Nenhuma referência adicionada.</p>
                @endif
            </div>

            <div class="border-t border-gray-100 dark:border-neutral-700 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-700 dark:text-neutral-300">Contas bancárias do contato</p>
                    <button type="button" wire:click="addContactBankAccountRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
                </div>
                @foreach ($contactBankAccountRows as $i => $row)
                    <div wire:key="bank-account-{{ $i }}" class="grid grid-cols-4 gap-2 items-start mb-2">
                        <input type="text" placeholder="Banco" wire:model="contactBankAccountRows.{{ $i }}.bank" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="text" placeholder="Agência" wire:model="contactBankAccountRows.{{ $i }}.agency" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <input type="text" placeholder="Conta" wire:model="contactBankAccountRows.{{ $i }}.account" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <div class="flex gap-2">
                            <input type="text" placeholder="Titular" wire:model="contactBankAccountRows.{{ $i }}.holder" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                            <button type="button" wire:click="removeContactBankAccountRow({{ $i }})" title="Remover" class="shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                        </div>
                    </div>
                @endforeach
                @if (empty($contactBankAccountRows))
                    <p class="text-gray-400 dark:text-neutral-500">Nenhuma conta adicionada.</p>
                @endif
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações</label>
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
                @error('notes') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Salvar
                </button>
                <button type="button" wire:click="cancel" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg overflow-hidden">
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
                                <button wire:click="edit({{ $contact->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
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

    <div class="mt-3">
        {{ $contacts->links() }}
    </div>
</div>
