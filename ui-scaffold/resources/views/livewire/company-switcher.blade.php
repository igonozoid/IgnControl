<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" @click.away="open = false"
        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        {{ $current?->name ?? 'Selecione uma empresa' }}
        <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
        <div class="py-1">
            @forelse ($companies as $company)
                <button wire:click="switchTo({{ $company->id }})"
                    class="block w-full text-left px-4 py-2 text-sm {{ $current?->id === $company->id ? 'font-semibold text-indigo-600' : 'text-gray-700' }} hover:bg-gray-100">
                    {{ $company->name }}
                </button>
            @empty
                <p class="px-4 py-2 text-sm text-gray-500">Você não está vinculado a nenhuma empresa.</p>
            @endforelse
        </div>
    </div>
</div>
