<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Seu nome" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="venue_name" value="Nome do estabelecimento" />
            <x-text-input id="venue_name" class="block mt-1 w-full" type="text" name="venue_name" :value="old('venue_name')" required autocomplete="organization" />
            <x-input-error :messages="$errors->get('venue_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="subdomain" value="Endereco do cardapio" />
            <x-text-input id="subdomain" class="block mt-1 w-full" type="text" name="subdomain" :value="old('subdomain')" required pattern="[a-z0-9-]+" autocomplete="off" />
            <p class="mt-1 text-xs text-gray-500">Use letras minusculas, numeros e hifen. Ex.: meu-restaurante</p>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="E-mail de acesso" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                Criar meu cardapio
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
