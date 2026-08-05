<div>
    <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações</label>
    <textarea wire:model="notes" rows="6" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
    @error('notes') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
</div>
