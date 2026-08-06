<div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
    <h1 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-1"><x-icon name="key" class="w-4 h-4" />Acessos</h1>
    <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
        Acesso de um usuário através de várias empresas — o nível de cada módulo pode ser diferente em cada uma. Só aparecem aqui as empresas em que você mesmo tem controle total.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-1 bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-lg p-3 text-xs h-fit">
            <label class="block font-medium text-gray-700 dark:text-neutral-300 mb-1">Buscar por e-mail</label>
            <form wire:submit="searchByEmail" class="flex gap-1.5">
                <input type="email" wire:model="searchEmail" placeholder="usuario@empresa.com" class="block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100 shadow-sm">
                <button type="submit" class="px-2 py-1 rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 hover:bg-gray-200 dark:hover:bg-neutral-600">
                    <x-icon name="check" class="w-3.5 h-3.5" />
                </button>
            </form>
            @if ($searchError)
                <p class="text-red-600 dark:text-red-400 mt-1">{{ $searchError }}</p>
            @endif

            <p class="font-medium text-gray-700 dark:text-neutral-300 mt-4 mb-1">Ou escolha alguém que já conhece</p>
            <div class="space-y-0.5 max-h-80 overflow-y-auto">
                @forelse ($users as $user)
                    <button wire:click="selectUser({{ $user->id }})"
                        @class([
                            'w-full text-left px-2 py-1.5 rounded-md',
                            'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' => $selectedUser?->id === $user->id,
                            'text-gray-600 hover:bg-gray-50 dark:text-neutral-300 dark:hover:bg-neutral-700/50' => $selectedUser?->id !== $user->id,
                        ])>
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-[11px] text-gray-400 dark:text-neutral-500">{{ $user->email }}</div>
                    </button>
                @empty
                    <p class="text-gray-400 dark:text-neutral-500">Ninguém ainda — busque por e-mail acima.</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-3">
            @if (! $selectedUser)
                <div class="bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-lg p-8 text-center text-xs text-gray-400 dark:text-neutral-500">
                    Escolha um usuário à esquerda pra ver e editar o acesso dele por empresa.
                </div>
            @else
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs">
                        <span class="font-medium text-gray-900 dark:text-neutral-100">{{ $selectedUser->name }}</span>
                        <span class="text-gray-400 dark:text-neutral-500">— {{ $selectedUser->email }}</span>
                    </div>
                    <button wire:click="clearSelection" class="text-gray-400 hover:text-gray-600 dark:text-neutral-500 dark:hover:text-neutral-300">
                        <x-icon name="x-mark" class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div class="bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-lg overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-neutral-700/50 text-gray-500 dark:text-neutral-400 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left sticky left-0 bg-gray-50 dark:bg-neutral-700/50">Empresa</th>
                                @foreach ($modules as $module)
                                    <th class="px-2 py-2 text-left capitalize whitespace-nowrap">{{ $module }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">
                            @forelse ($companies as $company)
                                <tr wire:key="company-{{ $company->id }}">
                                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-neutral-100 sticky left-0 bg-white dark:bg-neutral-800 whitespace-nowrap">
                                        {{ $company->name }}
                                    </td>
                                    @foreach ($modules as $module)
                                        @php $level = $levelsByCompany[$company->id][$module] ?? 'none'; @endphp
                                        <td class="px-2 py-1.5">
                                            <select
                                                wire:change="setLevel({{ $company->id }}, '{{ $module }}', $event.target.value)"
                                                @class([
                                                    'rounded-md text-[11px] border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100',
                                                    'text-gray-400 dark:text-neutral-500' => $level === 'none',
                                                    'text-blue-700 dark:text-blue-400' => $level === 'read',
                                                    'text-green-700 dark:text-green-400 font-medium' => $level === 'full',
                                                ])>
                                                @foreach ($levelsOptions as $option)
                                                    <option value="{{ $option }}" @selected($option === $level)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($modules) + 1 }}" class="px-3 py-6 text-center text-gray-400 dark:text-neutral-500">
                                        Você não tem controle total ("admin") em nenhuma empresa ainda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-neutral-500 mt-2">
                    Cada seleção salva na hora. Zerar todos os módulos de uma empresa remove o usuário dela automaticamente.
                </p>
            @endif
        </div>
    </div>
</div>
