<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if(Auth::user()->role === 'owner')
                        <x-nav-link :href="route('owner.catalog')" :active="request()->routeIs('owner.catalog')">Cardápio</x-nav-link>
                        <x-nav-link :href="route('owner.tables')" :active="request()->routeIs('owner.tables')">Mesas</x-nav-link>
                        <x-nav-link :href="route('owner.team')" :active="request()->routeIs('owner.team*')">Equipe</x-nav-link>
                        <x-nav-link :href="route('owner.settings')" :active="request()->routeIs('owner.settings*')">Configurações</x-nav-link>
                        <x-nav-link :href="route('operations.index')" :active="request()->routeIs('operations.*')">Operação</x-nav-link>
                    @elseif(Auth::user()->role === 'waiter')
                        <x-nav-link :href="route('operations.index')" :active="request()->routeIs('operations.*')">Salão</x-nav-link>
                    @elseif(Auth::user()->role === 'kitchen')
                        <x-nav-link :href="route('kds.index')" :active="request()->routeIs('kds.*')">Cozinha</x-nav-link>
                    @elseif(Auth::user()->role === 'cashier')
                        <x-nav-link :href="route('cashier.index')" :active="request()->routeIs('cashier.*')">Caixa</x-nav-link>
                    @elseif(Auth::user()->role === 'platform_owner')
                        <x-nav-link :href="route('admin.platform')" :active="request()->routeIs('admin.*')">Administração</x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(session()->has('impersonator_id'))
                            <form method="POST" action="{{ route('admin.leave') }}">@csrf
                                <x-dropdown-link href="#" onclick="event.preventDefault(); this.closest('form').submit();">Retornar à administração</x-dropdown-link>
                            </form>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Sair
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(Auth::user()->role === 'owner')
                <x-responsive-nav-link :href="route('owner.catalog')" :active="request()->routeIs('owner.catalog')">Cardápio</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('owner.tables')" :active="request()->routeIs('owner.tables')">Mesas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('owner.team')" :active="request()->routeIs('owner.team*')">Equipe</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('owner.settings')" :active="request()->routeIs('owner.settings*')">Configurações</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('operations.index')" :active="request()->routeIs('operations.*')">Operação</x-responsive-nav-link>
            @elseif(Auth::user()->role === 'waiter')
                <x-responsive-nav-link :href="route('operations.index')" :active="request()->routeIs('operations.*')">Salão</x-responsive-nav-link>
            @elseif(Auth::user()->role === 'kitchen')
                <x-responsive-nav-link :href="route('kds.index')" :active="request()->routeIs('kds.*')">Cozinha</x-responsive-nav-link>
            @elseif(Auth::user()->role === 'cashier')
                <x-responsive-nav-link :href="route('cashier.index')" :active="request()->routeIs('cashier.*')">Caixa</x-responsive-nav-link>
            @elseif(Auth::user()->role === 'platform_owner')
                <x-responsive-nav-link :href="route('admin.platform')" :active="request()->routeIs('admin.*')">Administração</x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                                Sair
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
