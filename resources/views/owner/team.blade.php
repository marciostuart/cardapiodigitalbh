<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Equipe e acessos</h2><p class="text-sm text-slate-500">Crie acessos separados para salão, cozinha e caixa.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('owner.team.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">@csrf
            <h3 class="text-lg font-bold">Novo membro</h3><p class="mt-1 text-sm text-slate-500">A pessoa entra pelo login normal usando o e-mail e a senha temporária.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2"><input name="name" class="rounded-xl border-slate-300" placeholder="Nome completo" required><input name="email" type="email" class="rounded-xl border-slate-300" placeholder="E-mail de acesso" required><select name="role" class="rounded-xl border-slate-300"><option value="waiter">Garçom / salão</option><option value="kitchen">Cozinha</option><option value="cashier">Caixa</option></select><div></div><input name="password" type="password" class="rounded-xl border-slate-300" placeholder="Senha temporária" minlength="8" required><input name="password_confirmation" type="password" class="rounded-xl border-slate-300" placeholder="Confirmar senha" required></div>
            <p class="mt-2 text-xs text-slate-500">Use ao menos 8 caracteres, com maiúscula, minúscula e número.</p><button class="mt-4 rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white">Criar acesso</button>
        </form>
        <div class="space-y-4">
            @forelse($members as $member)
                <article class="grid gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:grid-cols-[1fr_auto] md:items-center">
                    <div><h3 class="font-bold">{{ $member->name }}</h3><p class="text-sm text-slate-500">{{ $member->email }}</p></div>
                    <div class="flex flex-wrap gap-2">
                        <form method="post" action="{{ route('owner.team.update', $member) }}" class="flex gap-2">@csrf @method('patch')<select name="role" class="rounded-lg border-slate-300 text-sm"><option value="waiter" @selected($member->role === 'waiter')>Garçom</option><option value="kitchen" @selected($member->role === 'kitchen')>Cozinha</option><option value="cashier" @selected($member->role === 'cashier')>Caixa</option></select><select name="active" class="rounded-lg border-slate-300 text-sm"><option value="1" @selected($member->active)>Ativo</option><option value="0" @selected(!$member->active)>Bloqueado</option></select><button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Salvar</button></form>
                        <details class="relative"><summary class="cursor-pointer rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Nova senha</summary><form method="post" action="{{ route('owner.team.password', $member) }}" class="absolute right-0 z-10 mt-2 grid w-72 gap-2 rounded-xl bg-white p-4 shadow-xl ring-1 ring-slate-200">@csrf @method('patch')<input name="password" type="password" class="rounded-lg border-slate-300 text-sm" placeholder="Nova senha" required><input name="password_confirmation" type="password" class="rounded-lg border-slate-300 text-sm" placeholder="Confirmar" required><button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Atualizar senha</button></form></details>
                    </div>
                </article>
            @empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Nenhum acesso de equipe cadastrado.</div>@endforelse
        </div>
    </div></div>
</x-app-layout>
