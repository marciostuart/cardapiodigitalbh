<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#EA4335">
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/cardapio-digital-bh-logo.svg') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/cardapio-digital-bh-favicon.webp') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @include('layouts.tracking-body')
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @php($pushVenue = auth()->user()?->tenant?->venues()->first())
            @if($pushVenue && filled(config('services.webpush.public_key')))
                <section data-web-push data-key-url="{{ route('push.public-key') }}" data-subscriptions-url="{{ route('push.subscriptions.store') }}" class="mx-auto mt-4 flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <p class="text-sm text-slate-600">Receba alertas de novos pedidos neste dispositivo. Você pode desativá-los a qualquer momento.</p>
                    <div class="flex items-center gap-3">
                        <span data-web-push-status role="status" aria-live="polite" class="text-sm text-slate-600"></span>
                        <button data-web-push-toggle type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">Ativar alertas</button>
                    </div>
                </section>
                <script src="{{ asset('js/staff-web-push.js') }}" defer></script>
            @endif

            @if(session()->has('impersonator_id'))
                <div class="bg-amber-300 px-4 py-3 text-center text-sm font-bold text-amber-950">
                    Modo suporte: você está acessando a conta de um cliente.
                    <form method="POST" action="{{ route('admin.leave') }}" class="ml-3 inline">@csrf<button class="underline">Retornar ao proprietário</button></form>
                </div>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
