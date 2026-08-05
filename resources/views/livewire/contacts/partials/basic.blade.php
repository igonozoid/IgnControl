<div class="space-y-3">
    <div>
        <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
        <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
        @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Tipo de pessoa</label>
        <div class="flex gap-4 text-gray-700 dark:text-neutral-300">
            <label class="flex items-center gap-1.5"><input type="radio" wire:model.live="document_type" value="individual" class="dark:bg-neutral-700 dark:border-neutral-600"> Física</label>
            <label class="flex items-center gap-1.5"><input type="radio" wire:model.live="document_type" value="company" class="dark:bg-neutral-700 dark:border-neutral-600"> Jurídica</label>
        </div>
        @error('document_type') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block font-medium text-gray-700 dark:text-neutral-300">Documento ({{ $document_type === 'company' ? 'CNPJ' : 'CPF' }})</label>
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
    </div>
</div>
