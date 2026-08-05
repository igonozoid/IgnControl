<div class="max-w-4xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <a href="{{ route('contacts.index') }}" wire:navigate class="text-xs text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200">&larr; Voltar pra Contatos</a>
            <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mt-1">
                <x-icon name="users" class="w-4 h-4" />
                {{ $contact ? $contact->name : 'Novo contato' }}
            </h1>
        </div>
        @if ($is_employee && $contact)
            <a href="{{ route('hr.profile', $contact) }}" wire:navigate class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:text-green-800">
                <x-icon name="briefcase" class="w-3.5 h-3.5" />
                Ver ficha de RH
            </a>
        @endif
    </div>

    <div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 dark:border-neutral-700">
        @foreach (['basic' => 'Dados básicos', 'credit' => 'Crédito', 'references' => 'Referências', 'notes' => 'Observações'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" class="px-3 py-2 text-xs font-medium border-b-2 -mb-px {{ $tab === $key ? 'border-green-600 text-green-700 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-neutral-400 hover:text-gray-700 dark:hover:text-neutral-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <form wire:submit="save" class="space-y-3 text-xs">
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
            @if ($tab === 'basic')
                @include('livewire.contacts.partials.basic')
            @elseif ($tab === 'credit')
                @include('livewire.contacts.partials.credit')
            @elseif ($tab === 'references')
                @include('livewire.contacts.partials.references')
            @elseif ($tab === 'notes')
                @include('livewire.contacts.partials.notes')
            @endif
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md font-medium hover:bg-green-700">
                <x-icon name="check" />
                Salvar
            </button>
            <a href="{{ route('contacts.index') }}" wire:navigate class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                <x-icon name="x-mark" />
                Cancelar
            </a>
        </div>
    </form>
</div>
