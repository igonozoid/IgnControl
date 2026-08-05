<div class="max-w-4xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100">
            <x-icon name="lock" class="w-4 h-4" />
            Credenciais
        </h1>
        @if ($this->canWrite)
            <button wire:click="create" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                <x-icon name="plus" />
                Nova credencial
            </button>
        @endif
    </div>

    <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
        Catálogo de acessos externos (SPC, Serasa, bancos, portais em geral).
        A senha fica criptografada e some da tela por padrão — só aparece
        depois de clicar em "mostrar", e isso fica registrado na auditoria.
    </p>

    <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-3 mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Buscar por título</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100" placeholder="Digite pra buscar...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Categoria</label>
                <select wire:model.live="filterCategory" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="">Todas</option>
                    <option value="login">Login</option>
                    <option value="bookmark">Marcador</option>
                    <option value="vault">Cofre técnico</option>
                </select>
            </div>
        </div>
    </div>

    <x-slide-over show="showForm" close="cancel" title="{{ $editingId ? 'Editar credencial' : 'Nova credencial' }}">
        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Categoria</label>
                <select wire:model="category" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                    <option value="login">Login</option>
                    <option value="bookmark">Marcador</option>
                    <option value="vault">Cofre técnico</option>
                </select>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Título</label>
                <input type="text" wire:model="title" placeholder="Ex.: SPC Brasil" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('title') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">URL</label>
                <input type="url" wire:model="url" placeholder="https://..." class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('url') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Usuário</label>
                    <input type="text" wire:model="username" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-neutral-300">Senha</label>
                    <input type="text" wire:model="password" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                    <p class="text-gray-500 dark:text-neutral-400 mt-1">Guardada criptografada.</p>
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Observações</label>
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm"></textarea>
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Título</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Usuário</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Senha</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($credentials as $credential)
                    <tr wire:key="credential-{{ $credential->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">
                            @if ($credential->url)
                                <a href="{{ $credential->url }}" target="_blank" rel="noopener" class="flex items-center gap-1 hover:text-green-700 dark:hover:text-green-400">
                                    {{ $credential->title }}
                                    <x-icon name="arrow-top-right-on-square" class="w-3 h-3" />
                                </a>
                            @else
                                {{ $credential->title }}
                            @endif
                            <span class="text-gray-400 dark:text-neutral-500">— {{ ['login' => 'Login', 'bookmark' => 'Marcador', 'vault' => 'Cofre técnico'][$credential->category] ?? $credential->category }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $credential->username ?: '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400 font-mono">
                            @if (! $credential->password)
                                —
                            @elseif (in_array($credential->id, $revealed))
                                <span class="text-gray-900 dark:text-neutral-100">{{ $credential->password }}</span>
                                <button type="button" wire:click="hide({{ $credential->id }})" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-neutral-300">ocultar</button>
                            @else
                                ••••••••
                                <button type="button" wire:click="reveal({{ $credential->id }})" class="ml-1 text-green-600 dark:text-green-400 hover:text-green-800">mostrar</button>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                            @if ($this->canWrite)
                                <button wire:click="edit({{ $credential->id }})" title="Editar" class="inline-flex text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"><x-icon name="pencil" /></button>
                                <button wire:click="delete({{ $credential->id }})" wire:confirm="Tem certeza que quer excluir esta credencial?" title="Excluir" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-neutral-400">Nenhuma credencial cadastrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
