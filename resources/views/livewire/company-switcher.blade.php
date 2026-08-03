<div class="relative w-full" x-data="{ open: false }">
    <button @click="open = !open" @click.away="open = false"
        class="w-full inline-flex items-center justify-between px-3 py-1.5 border border-gray-300 dark:border-neutral-600 rounded-md text-sm font-medium text-gray-700 dark:text-neutral-200 bg-white dark:bg-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-600">
        <span class="truncate">{{ $current?->name ?? 'Selecione uma empresa' }}</span>
        <svg class="ml-2 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="absolute z-50 mt-1 w-full rounded-md shadow-lg bg-white dark:bg-neutral-700 ring-1 ring-black ring-opacity-5 dark:ring-neutral-600">
        <div class="py-1">
            @forelse ($companies as $company)
                <button wire:click="switchTo({{ $company->id }})"
                    class="block w-full text-left px-3 py-1.5 text-sm {{ $current?->id === $company->id ? 'font-semibold text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-neutral-200' }} hover:bg-gray-100 dark:hover:bg-neutral-600">
                    {{ $company->name }}
                </button>
            @empty
                <p class="px-3 py-1.5 text-sm text-gray-500 dark:text-neutral-400">Você não está vinculado a nenhuma empresa.</p>
            @endforelse
        </div>
    </div>
</div>
