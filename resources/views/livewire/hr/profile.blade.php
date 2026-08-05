<div class="max-w-4xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <a href="{{ route('hr.index') }}" wire:navigate class="text-xs text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200">&larr; Voltar pra RH</a>
            <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mt-1">
                <x-icon name="briefcase" class="w-4 h-4" />
                {{ $contact->name }}
            </h1>
        </div>
        <a href="{{ route('contacts.index') }}" wire:navigate class="text-xs text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200">Ver cadastro do contato</a>
    </div>

    <div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 dark:border-neutral-700">
        @foreach (['profile' => 'Perfil', 'salary' => 'Evolução salarial', 'vacation' => 'Férias', 'thirteenth' => '13º salário', 'benefit' => 'Benefícios', 'cost' => 'Custo/Encargos'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" class="px-3 py-2 text-xs font-medium border-b-2 -mb-px {{ $tab === $key ? 'border-green-600 text-green-700 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'profile')
        <form wire:submit="saveProfile" class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4 space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Função</label>
                    <input type="text" wire:model="job_title" placeholder="Ex.: Jardineiro(a)" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Categoria</label>
                    <select wire:model="staff_category" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="domestic_rural">Doméstico/Rural</option>
                        <option value="office">Escritório</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Tipo de vínculo</label>
                    <input type="text" wire:model="employment_type" placeholder="CLT, PF, Diarista, Estagiário..." class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Sindicato</label>
                    <input type="text" wire:model="union_name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Local de trabalho</label>
                    <input type="text" wire:model="workplace_location" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Dissídio de referência</label>
                    <input type="text" wire:model="dissidio_reference" placeholder="Ex.: Sal. Mín. Federal" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Cidade</label>
                    <input type="text" wire:model="city" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Estado</label>
                    <input type="text" wire:model="state" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Alíquota INSS patronal</label>
                    <input type="number" step="0.0001" wire:model="inss_rate" placeholder="Ex.: 0.08 = 8%" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    @error('inss_rate') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Horas mensais</label>
                    <input type="number" step="0.01" wire:model="monthly_hours" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Salário inicial</label>
                    <input type="number" step="0.01" wire:model="initial_salary" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Situação</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        <option value="active">Ativo</option>
                        <option value="terminated">Ex-funcionário</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Admissão</label>
                    <input type="date" wire:model="admission_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Desligamento</label>
                    <input type="date" wire:model="termination_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações</label>
                <textarea wire:model="profile_notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100"></textarea>
            </div>
            @if ($this->canWrite)
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Salvar perfil
                </button>
                <x-action-message class="ml-2 text-xs" on="profile-saved">Salvo.</x-action-message>
            @endif
        </form>
    @endif

    @if ($tab === 'salary')
        @include('livewire.hr.partials.salary')
    @endif

    @if ($tab === 'vacation')
        @include('livewire.hr.partials.vacation')
    @endif

    @if ($tab === 'thirteenth')
        @include('livewire.hr.partials.thirteenth')
    @endif

    @if ($tab === 'benefit')
        @include('livewire.hr.partials.benefit')
    @endif

    @if ($tab === 'cost')
        @include('livewire.hr.partials.cost')
    @endif

    <x-slide-over show="showForm" close="cancelEntry" title="{{ $editingId ? 'Editar' : 'Novo' }} — {{ ['salary' => 'Evolução salarial', 'vacation' => 'Férias', 'thirteenth' => '13º salário', 'benefit' => 'Benefício'][$formType] ?? '' }}">
        <form wire:submit="saveEntry" class="space-y-3 text-xs">
            @if ($formType === 'salary')
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data</label>
                    <input type="date" wire:model="form.effective_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    @error('form.effective_date') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Salário nominal</label>
                        <input type="number" step="0.01" wire:model="form.nominal_salary" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">% Dissídio</label>
                        <input type="number" step="0.000001" wire:model="form.dissidio_percent" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor líquido (acerto)</label>
                        <input type="number" step="0.01" wire:model="form.net_salary" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor-hora</label>
                        <input type="number" step="0.0001" wire:model="form.hourly_value" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Benefícios (valor)</label>
                    <input type="number" step="0.01" wire:model="form.benefits_value" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
            @elseif ($formType === 'vacation')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Início aquisitivo</label>
                        <input type="date" wire:model="form.period_start" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        @error('form.period_start') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Fim aquisitivo</label>
                        <input type="date" wire:model="form.period_end" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        @error('form.period_end') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Início do gozo</label>
                        <input type="date" wire:model="form.leave_start" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Fim do gozo</label>
                        <input type="date" wire:model="form.leave_end" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Data de pagamento</label>
                        <input type="date" wire:model="form.payment_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor pago</label>
                        <input type="number" step="0.01" wire:model="form.amount_paid" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                </div>
            @elseif ($formType === 'thirteenth')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Ano</label>
                        <input type="number" wire:model="form.year" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                        @error('form.year') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor pago</label>
                        <input type="number" step="0.01" wire:model="form.amount_paid" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Data de pagamento</label>
                    <input type="date" wire:model="form.payment_date" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                </div>
            @elseif ($formType === 'benefit')
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                    <input type="text" wire:model="form.name" placeholder="Ex.: Unimed, Seguro de Vida" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    @error('form.name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Valor mensal</label>
                    <input type="number" step="0.01" wire:model="form.monthly_value" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    @error('form.monthly_value') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <label class="flex items-center gap-1.5 text-gray-700 dark:text-neutral-300"><input type="checkbox" wire:model="form.active" class="dark:bg-neutral-700 dark:border-neutral-600"> Ativo (entra no total mensal)</label>
            @endif

            @if (($formType ?? null) !== null)
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações</label>
                    <textarea wire:model="form.notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100"></textarea>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                    <x-icon name="check" />
                    Salvar
                </button>
                <button type="button" wire:click="cancelEntry" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>
</div>
