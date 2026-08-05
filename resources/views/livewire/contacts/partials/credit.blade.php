<div class="space-y-3">
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
    <div class="grid grid-cols-2 gap-4">
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
