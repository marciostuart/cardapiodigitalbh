<x-guest-layout>
    <h2>Crie seu cardápio.</h2>
    <p class="subtitle">Cadastre seu estabelecimento e comece a organizar sua operação.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <x-input-label for="venue_name" value="Nome do estabelecimento" />
            <x-text-input id="venue_name" class="block mt-1 w-full" type="text" name="venue_name" :value="old('venue_name')" required autofocus autocomplete="organization" />
            <x-input-error :messages="$errors->get('venue_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="subdomain" value="Endereço do seu cardápio" />
            <div class="flex mt-1">
                <x-text-input id="subdomain" class="block w-full rounded-r-none" type="text" name="subdomain" :value="old('subdomain')" required pattern="[a-z0-9-]+" autocomplete="off" oninput="this.value=this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'')" />
                <span class="inline-flex items-center px-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-xs text-gray-500">.cardapiodigitalbh.com.br</span>
            </div>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="name" value="Seu nome" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="E-mail de acesso" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Crie uma senha" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" minlength="8" />
            <p class="mt-1 text-xs text-slate-500">Mínimo de 8 caracteres, com maiúscula, minúscula e número.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirme sua senha" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="mt-6 flex items-start gap-3">
            <input id="accept_terms" name="accept_terms" type="checkbox" value="1" required @checked(old('accept_terms')) class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
            <label for="accept_terms" class="text-sm text-slate-700">
                Li e aceito os <a class="font-semibold text-red-600 underline" href="{{ route('marketing.terms') }}" target="_blank" rel="noopener">Termos de Uso</a>
                e estou ciente da <a class="font-semibold text-red-600 underline" href="{{ route('marketing.privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
            </label>
        </div>
        <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" />

        <x-primary-button class="submit mt-6">Criar meu cardápio grátis</x-primary-button>
    </form>

    <p class="helper">Já possui uma conta? <a href="{{ route('login') }}">Entrar no painel</a></p>
</x-guest-layout>
