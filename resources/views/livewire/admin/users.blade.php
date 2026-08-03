<div class="max-w-5xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100"><x-icon name="shield" class="w-4 h-4" />Usuários e Permissões</h1>
        <button wire:click="$set('showInviteForm', true)" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-md text-xs font-medium hover:bg-indigo-700">
            <x-icon name="user-plus" />
            Novo usuário
        </button>
    </div>

    <x-slide-over show="showInviteForm" close="$set('showInviteForm', false)" title="Novo usuário">
        <form wire:submit="inviteUser" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Nome</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('name') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">E-mail</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('email') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Senha</label>
                <input type="password" wire:model="password" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                @error('password') <span class="text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-medium text-gray-700 dark:text-neutral-300">Confirmar senha</label>
                <input type="password" wire:model="password_confirmation" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
            </div>

            <p class="text-gray-500 dark:text-neutral-400">
                O usuário nasce sem acesso a nenhum módulo. Depois de criado, use "Editar permissões" na lista pra liberar o que ele precisa.
            </p>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-md font-medium hover:bg-indigo-700">
                    <x-icon name="check" />
                    Criar usuário
                </button>
                <button type="button" wire:click="$set('showInviteForm', false)" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="x-mark" />
                    Cancelar
                </button>
            </div>
        </form>
    </x-slide-over>

    <x-slide-over show="editingUserId" close="cancelEditingPermissions" title="Permissões — {{ $users->firstWhere('id', $editingUserId)?->name }}">
        <form wire:submit="savePermissions" class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($modules as $module)
                    <div>
                        <label class="block font-medium text-gray-500 dark:text-neutral-400 capitalize">{{ $module }}</label>
                        <select wire:model="levels.{{ $module }}" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
                            @foreach ($levels_options as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-md font-medium hover:bg-indigo-700">
                    <x-icon name="check" />
                    Salvar permissões
                </button>
                <button type="button" wire:click="cancelEditingPermissions" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 rounded-md font-medium hover:bg-gray-200 dark:hover:bg-neutral-600">
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Nome</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">E-mail</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase">Acessos</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-2 text-xs text-gray-900 dark:text-neutral-100">{{ $user->name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">{{ $user->email }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-neutral-400">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($modules as $module)
                                    @php $level = $user->modulePermissions[$module] ?? 'none'; @endphp
                                    <span @class([
                                        'inline-flex px-1.5 py-0.5 rounded text-xs',
                                        'bg-gray-100 text-gray-400 dark:bg-neutral-700 dark:text-neutral-500' => $level === 'none',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $level === 'read',
                                        'bg-green-100 text-[#15803d] dark:bg-green-500/10 dark:text-[#86efac]' => $level === 'full',
                                    ])>
                                        {{ $module }}: {{ $level }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right text-xs whitespace-nowrap space-x-2">
                            <button wire:click="editPermissions({{ $user->id }})" title="Editar permissões" class="inline-flex text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"><x-icon name="shield" /></button>
                            @if ($user->id !== auth()->id())
                                <button wire:click="removeUser({{ $user->id }})" wire:confirm="Remover este usuário da empresa?" title="Remover" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><x-icon name="trash" /></button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
