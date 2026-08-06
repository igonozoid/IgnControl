<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="building" class="w-4 h-4" />Empresas</h1>
        <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
            <x-icon name="plus" />
            Nova empresa
        </button>
    </div>

    <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
        Empresas que você participa. Criar uma nova empresa aqui já troca automaticamente pra ela — os módulos ficam liberados em modo completo pra você, e podem ser ajustados depois em "Usuários e Permissões". Financeiro, Contatos, Relatórios, Agenda, Administração, Credenciais e Auditoria são o núcleo do sistema e ficam sempre disponíveis; RH, Estoque, Vendas, Rural e Centros de Custo são opcionais e se marcam abaixo, por empresa.
    </p>

    <div class="bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-lg overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50 dark:bg-neutral-700/50 text-gray-500 dark:text-neutral-400 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Razão social</th>
                    <th class="px-4 py-2 text-left">CNPJ/CPF</th>
                    <th class="px-4 py-2 text-left">Moeda base</th>
                    <th class="px-4 py-2 text-left">Módulos</th>
                    <th class="px-4 py-2 text-left">Situação</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">
                @forelse ($companies as $company)
                    <tr class="{{ $company->id === auth()->user()->current_company_id ? 'bg-green-50/50 dark:bg-green-500/5' : '' }}">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-neutral-100">
                            <div class="flex items-center gap-2">
                                @if ($company->logo_path)
                                    <img src="{{ route('admin.companies.logo', $company) }}" alt="" class="w-6 h-6 rounded object-cover border border-gray-200 dark:border-neutral-600">
                                @endif
                                {{ $company->name }}
                            </div>
                            @if ($company->id === auth()->user()->current_company_id)
                                <span class="ms-1.5 text-[10px] font-normal text-green-700 dark:text-green-400">(ativa)</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-600 dark:text-neutral-300">{{ $company->legal_name ?: '—' }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-neutral-300">{{ $company->tax_id ?: '—' }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-neutral-300">{{ $company->base_currency_code }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-neutral-300">
                            @php $active = collect($moduleLabels)->only($company->enabled_modules ?? [])->values(); @endphp
                            {{ $active->isNotEmpty() ? $active->implode(', ') : '—' }}
                        </td>
                        <td class="px-4 py-2">
                            @if ($company->is_active)
                                <span class="text-green-700 dark:text-green-400">Ativa</span>
                            @else
                                <span class="text-gray-400 dark:text-neutral-500">Inativa</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="edit({{ $company->id }})" title="Editar" class="text-gray-400 hover:text-green-600 dark:text-neutral-500 dark:hover:text-green-400">
                                <x-icon name="pencil" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400 dark:text-neutral-500">Nenhuma empresa encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-slide-over show="showForm" close="cancel" :title="$editingId ? 'Editar empresa' : 'Nova empresa'">
        <form wire:submit="save" class="space-y-4 text-xs">
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    @if ($this->logoPreviewUrl)
                        <img src="{{ $this->logoPreviewUrl }}" alt="Logo da empresa" class="w-16 h-16 rounded object-cover border border-gray-200 dark:border-neutral-600">
                    @else
                        <div class="w-16 h-16 rounded bg-gray-100 dark:bg-neutral-700 flex items-center justify-center text-gray-400 dark:text-neutral-500">
                            <x-icon name="building" class="w-6 h-6" />
                        </div>
                    @endif
                    <div class="mt-1 flex flex-col gap-1">
                        <label class="text-green-600 dark:text-green-400 hover:text-green-800 cursor-pointer">
                            Trocar logo
                            <input type="file" wire:model="logo" accept="image/*" class="hidden">
                        </label>
                        @if ($this->logoPreviewUrl)
                            <button type="button" wire:click="removeLogoNow" class="text-red-600 dark:text-red-400 hover:text-red-800 text-left">Remover</button>
                        @endif
                    </div>
                    <div wire:loading wire:target="logo" class="text-gray-400 dark:text-neutral-500 mt-1">Enviando...</div>
                    @error('logo') <span class="text-red-600 dark:text-red-400 block mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Tipo de pessoa</label>
                        <div class="flex gap-4 text-gray-700 dark:text-neutral-300">
                            <label class="flex items-center gap-1.5"><input type="radio" wire:model.live="person_type" value="PF" class="dark:bg-neutral-700 dark:border-neutral-600"> Física</label>
                            <label class="flex items-center gap-1.5"><input type="radio" wire:model.live="person_type" value="PJ" class="dark:bg-neutral-700 dark:border-neutral-600"> Jurídica</label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                        <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Razão social {{ $this->isCnpjDocument ? '' : '/ nome completo' }}</label>
                <input type="text" wire:model="legal_name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('legal_name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">{{ $this->isCnpjDocument ? 'CNPJ' : 'CPF' }}</label>
                    <input type="text" wire:model="tax_id" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('tax_id') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    @if ($this->isCnpjDocument)
                        <button type="button" wire:click="buscarCnpj" wire:loading.attr="disabled" wire:target="buscarCnpj"
                            class="mt-1.5 flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 hover:bg-gray-200 dark:hover:bg-neutral-600 disabled:opacity-50">
                            <span wire:loading.remove wire:target="buscarCnpj">Busca Básica</span>
                            <span wire:loading wire:target="buscarCnpj">Consultando...</span>
                        </button>
                    @endif
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">{{ $this->isCnpjDocument ? 'Inscrição Estadual' : 'Documento secundário' }}</label>
                    <input type="text" wire:model="document_secondary" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('document_secondary') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">E-mail</label>
                    <input type="email" wire:model="email" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('email') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Telefone</label>
                    <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('phone') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Site</label>
                <input type="text" wire:model="website" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('website') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
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

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Bairro</label>
                    <input type="text" wire:model="district" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('district') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Cidade</label>
                    <input type="text" wire:model="city" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    @error('city') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">UF</label>
                    <input type="text" wire:model="state" maxlength="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm uppercase">
                    @error('state') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
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
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Moeda base</label>
                <select wire:model="base_currency_code" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <option value="">Selecione...</option>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }} — {{ $currency->name }}</option>
                    @endforeach
                </select>
                @error('base_currency_code') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="inline-flex items-center gap-2 font-medium text-gray-700 dark:text-neutral-300">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 dark:border-neutral-600 text-green-600 shadow-sm focus:ring-green-500">
                    Ativa
                </label>
            </div>

            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Módulos opcionais</label>
                <p class="text-[11px] text-gray-500 dark:text-neutral-400 mb-2">
                    Só aparecem no menu, pra quem tiver permissão, os módulos marcados aqui.
                </p>
                <div class="space-y-1.5">
                    @foreach ($moduleLabels as $module => $label)
                        <label class="flex items-center gap-2 font-normal text-gray-700 dark:text-neutral-300">
                            <input type="checkbox" wire:model="optionalModules.{{ $module }}" class="rounded border-gray-300 dark:border-neutral-600 text-green-600 shadow-sm focus:ring-green-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
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
</div>
