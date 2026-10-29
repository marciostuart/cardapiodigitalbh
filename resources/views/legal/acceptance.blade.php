<x-guest-layout>
    <h2>Confirme os termos para continuar</h2>
    <p class="subtitle">Leia os documentos e confirme seu aceite. Vamos guardar a versão e a data deste registro.</p>

    <form method="POST" action="{{ route('legal.acceptance.store') }}">
        @csrf
        <div class="mt-6 flex items-start gap-3">
            <input id="accept_terms" name="accept_terms" type="checkbox" value="1" required @checked(old('accept_terms')) class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
            <label for="accept_terms" class="text-sm text-slate-700">
                Li e aceito os <a class="font-semibold text-red-600 underline" href="{{ route('marketing.terms') }}" target="_blank" rel="noopener">Termos de Uso</a>
                e estou ciente da <a class="font-semibold text-red-600 underline" href="{{ route('marketing.privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
            </label>
        </div>
        <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" />
        <x-primary-button class="submit mt-6">Confirmar e continuar</x-primary-button>
    </form>
    <p class="mt-4 text-xs text-slate-500">Versão dos termos: {{ $termsVersion }} · Política de privacidade: {{ $privacyVersion }}</p>
</x-guest-layout>
