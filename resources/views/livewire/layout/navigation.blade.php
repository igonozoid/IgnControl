<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 w-60 h-screen bg-white dark:bg-neutral-800 border-r border-gray-100 dark:border-neutral-700 flex flex-col transition-transform duration-200 ease-in-out lg:relative lg:translate-x-0 shrink-0"
>
    <!-- Logo -->
    <div class="h-14 flex items-center px-5 border-b border-gray-100 dark:border-neutral-700 shrink-0">
        <a href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo class="h-8 w-auto fill-current text-gray-800 dark:text-neutral-100" />
        </a>
    </div>

    <!-- Empresa ativa -->
    <div class="px-3 py-3 border-b border-gray-100 dark:border-neutral-700">
        <livewire:company-switcher />
    </div>

    <!-- Links de navegação -->
    <nav class="flex-1 px-2.5 py-3 space-y-0.5 overflow-y-auto text-xs">
        <a href="{{ route('dashboard') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('dashboard') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="dashboard" />
            {{ __('Dashboard') }}
        </a>

        <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Financeiro</p>
        <a href="{{ route('financial-accounts.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('financial-accounts.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="bank" />
            {{ __('Contas Financeiras') }}
        </a>
        <a href="{{ route('categories.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('categories.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="tag" />
            {{ __('Categorias') }}
        </a>
        <a href="{{ route('cost-centers.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('cost-centers.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="briefcase" />
            {{ __('Centros de Custo') }}
        </a>
        <a href="{{ route('currencies.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('currencies.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="coins" />
            {{ __('Moedas') }}
        </a>
        <a href="{{ route('financial-entries.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('financial-entries.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="list" />
            {{ __('Lançamentos') }}
        </a>

        <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Contatos</p>
        <a href="{{ route('contacts.index') }}" wire:navigate
            class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('contacts.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
            <x-icon name="users" />
            {{ __('Contatos') }}
        </a>

        @if (auth()->user()->hasModuleAccess('hr', 'read'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">RH</p>
            <a href="{{ route('hr.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('hr.*') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="briefcase" />
                {{ __('Funcionários') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('sales', 'read'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Vendas</p>
            <a href="{{ route('sales.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('sales.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="list" />
                {{ __('Pedidos') }}
            </a>
            <a href="{{ route('product-tax-profiles.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('product-tax-profiles.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="filter" />
                {{ __('Perfis Tributários') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('inventory', 'read'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Estoque</p>
            <a href="{{ route('stock-movements.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('stock-movements.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="briefcase" />
                {{ __('Movimentações') }}
            </a>
            <a href="{{ route('products.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('products.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="tag" />
                {{ __('Produtos') }}
            </a>
            <a href="{{ route('product-categories.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('product-categories.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="tag" />
                {{ __('Categorias') }}
            </a>
            <a href="{{ route('stock-locations.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('stock-locations.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="bank" />
                {{ __('Locais') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('agenda', 'read'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Agenda</p>
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('tasks.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="calendar" />
                {{ __('Tarefas') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('reports', 'read'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Relatórios</p>
            <a href="{{ route('reports.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('reports.*') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="chart" />
                {{ __('Relatórios') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('admin', 'full'))
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-neutral-500 uppercase">Administração</p>
            <a href="{{ route('admin.users.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.users.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="shield" />
                {{ __('Usuários e Permissões') }}
            </a>
            <a href="{{ route('admin.period-lock.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.period-lock.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="lock" />
                {{ __('Fechamento de Período') }}
            </a>
            <a href="{{ route('admin.audit.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.audit.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="document" />
                {{ __('Auditoria') }}
            </a>
        @endif

        @if (auth()->user()->hasModuleAccess('credentials', 'read'))
            <a href="{{ route('admin.credentials.index') }}" wire:navigate
                class="flex items-center gap-2 pl-4 pr-3 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.credentials.index') ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700/50 dark:hover:text-white' }}">
                <x-icon name="key" />
                {{ __('Credenciais') }}
            </a>
        @endif
    </nav>

    <!-- Tema claro/escuro -->
    <div class="border-t border-gray-100 dark:border-neutral-700 px-3 py-2" x-data="{
        dark: localStorage.theme === 'dark',
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.theme = this.dark ? 'dark' : 'light';
        }
    }">
        <button @click="toggle()" class="w-full flex items-center justify-between px-2 py-1.5 rounded-md text-xs text-gray-600 hover:bg-gray-50 dark:text-neutral-300 dark:hover:bg-neutral-700/50">
            <span x-text="dark ? 'Modo escuro' : 'Modo claro'"></span>
            <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors" :class="dark ? 'bg-green-600' : 'bg-gray-300'">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" :class="dark ? 'translate-x-4' : 'translate-x-0.5'"></span>
            </span>
        </button>
    </div>

    <!-- Usuário -->
    <div class="border-t border-gray-100 dark:border-neutral-700 p-2.5" x-data="{ open: false }">
        <button @click="open = !open" @click.away="open = false"
            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs font-medium text-gray-600 hover:bg-gray-50 dark:text-neutral-300 dark:hover:bg-neutral-700/50">
            <span x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div x-show="open" x-cloak class="mt-1 rounded-md border border-gray-100 dark:border-neutral-700 shadow-sm overflow-hidden">
            <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 dark:text-neutral-200 dark:hover:bg-neutral-700/50 dark:bg-neutral-800">
                {{ __('Profile') }}
            </a>
            <button wire:click="logout" class="w-full text-start block px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 dark:text-neutral-200 dark:hover:bg-neutral-700/50 dark:bg-neutral-800">
                {{ __('Log Out') }}
            </button>
        </div>
    </div>
</aside>
