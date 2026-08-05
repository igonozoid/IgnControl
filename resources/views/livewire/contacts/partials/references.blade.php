<div class="space-y-4">
    @if ($document_type === 'company')
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="font-semibold text-gray-700 dark:text-neutral-300">Contatos de departamento</p>
                <button type="button" wire:click="addDepartmentContactRow" class="text-green-600 dark:text-green-400 hover:text-green-800">+ adicionar</button>
            </div>
            <p class="text-gray-400 dark:text-neutral-500 mb-2">Pessoas de contato dentro dessa empresa — útil quando o contato "físico" muda por área.</p>
            @foreach ($departmentContactRows as $i => $row)
                <div wire:key="department-contact-{{ $i }}" class="grid grid-cols-4 gap-2 items-start mb-2">
                    <input type="text" placeholder="Nome" wire:model="departmentContactRows.{{ $i }}.name" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <input type="text" placeholder="Cargo" wire:model="departmentContactRows.{{ $i }}.role" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <input type="text" placeholder="Ramal" wire:model="departmentContactRows.{{ $i }}.extension" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <div class="flex gap-2">
                        <input type="email" placeholder="E-mail" wire:model="departmentContactRows.{{ $i }}.email" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                        <button type="button" wire:click="removeDepartmentContactRow({{ $i }})" title="Remover" class="shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 mt-1"><x-icon name="trash" /></button>
                    </div>
                </div>
            @endforeach
            @if (empty($departmentContactRows))
                <p class="text-gray-400 dark:text-neutral-500">Nenhum contato de departamento adicionado.</p>
            @endif
        </div>

        <div class="border-t border-gray-100 dark:border-neutral-700 pt-3"></div>
    @endif

    <div>
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
</div>
