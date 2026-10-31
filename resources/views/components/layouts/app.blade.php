<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}ENRICA+</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased" x-data="{ menu: false }">
@php
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Painel', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
        ['route' => 'transactions.index', 'match' => 'transactions.*', 'label' => 'Lançamentos', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ['route' => 'accounts.index', 'match' => 'accounts.*', 'label' => 'Contas', 'icon' => 'M3 10h18M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
        ['route' => 'budgets.index', 'match' => 'budgets.*', 'label' => 'Orçamentos', 'icon' => 'M9 17v-6m4 6V7m4 10v-3M5 21h14'],
        ['route' => 'goals.index', 'match' => 'goals.*', 'label' => 'Metas', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Relatórios', 'icon' => 'M11 3a9 9 0 109 9h-9V3zM14 3.5A9 9 0 0120.5 10H14V3.5z'],
        ['route' => 'categories.index', 'match' => 'categories.*', 'label' => 'Categorias', 'icon' => 'M7 7h.01M7 3h5l9 9-7 7-9-9V5a2 2 0 012-2z'],
    ];
    $invest = [
        ['route' => 'investments.index', 'match' => 'investments.index|investments.assets.*', 'label' => 'Carteira', 'icon' => 'M3 17l6-6 4 4 8-8M14 7h7v7'],
        ['route' => 'investments.profile.edit', 'match' => 'investments.profile.*', 'label' => 'Perfil de investidor', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ['route' => 'investments.advisor', 'match' => 'investments.advisor*', 'label' => 'Assistente IA', 'icon' => 'M9.75 3.1v5.71a2.25 2.25 0 01-.66 1.6L5 14.5m4.75-11.4a24 24 0 014.5 0m0 0v5.71c0 .6.24 1.17.66 1.6L19.8 15.3M14.25 3.1c.25.02.5.05.75.08M5 14.5l-1.57 1.57c-1.4 1.4-.41 3.84 1.57 3.84h14c1.98 0 2.97-2.4 1.57-3.84L19.8 15.3M5 14.5h14.8'],
    ];
    $isActive = fn ($pattern) => collect(explode('|', $pattern))->contains(fn ($p) => request()->routeIs($p));
@endphp

<div class="flex min-h-screen">
    {{-- Barra lateral --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform bg-brand-900 text-slate-100 transition lg:static lg:translate-x-0"
           :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-2 px-6">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 text-lg font-bold text-white">E+</span>
            <span class="text-lg font-semibold tracking-tight">ENRICA<span class="text-brand-500">+</span></span>
        </div>

        {{-- Seletor de espaço --}}
        <div class="px-4" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg bg-white/10 px-3 py-2 text-left text-sm hover:bg-white/15">
                <span>
                    <span class="block text-[11px] uppercase tracking-wide text-slate-300">{{ $currentWorkspace->type->label() }}</span>
                    <span class="block font-medium">{{ $currentWorkspace->name }}</span>
                </span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" class="mt-2 overflow-hidden rounded-lg bg-white text-sm text-slate-700 shadow-lg">
                @foreach (auth()->user()->workspaces as $ws)
                    <form method="POST" action="{{ route('workspaces.switch', $ws->id) }}">
                        @csrf
                        <button class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-slate-50 {{ $ws->id === $currentWorkspace->id ? 'font-semibold text-brand-700' : '' }}">
                            <span>{{ $ws->name }}</span><span class="text-xs text-slate-400">{{ $ws->type->label() }}</span>
                        </button>
                    </form>
                @endforeach
                <a href="{{ route('workspaces.create') }}" class="block border-t border-slate-100 px-3 py-2 text-brand-700 hover:bg-slate-50">+ Novo espaço</a>
                <a href="{{ route('workspaces.index') }}" class="block px-3 py-2 hover:bg-slate-50">Gerenciar espaços</a>
            </div>
        </div>

        <nav class="mt-6 space-y-1 px-3 text-sm">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $isActive($item['match']) ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Investimentos</p>
            @foreach ($invest as $item)
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $isActive($item['match']) ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Espaço</p>
            <a href="{{ route('workspaces.settings') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('workspaces.settings') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Membros e configurações
            </a>
        </nav>
    </aside>
    <div x-show="menu" x-cloak @click="menu = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-8">
            <button class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" @click="menu = true" aria-label="Abrir menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="hidden text-sm text-slate-500 lg:block">{{ ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}</div>
            <div class="flex items-center gap-3">
                @can('write')
                    <a href="{{ route('transactions.create') }}" class="btn-primary btn-sm">+ Lançamento</a>
                @endcan
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-full bg-slate-100 py-1 pl-1 pr-3 text-sm hover:bg-slate-200">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-48 rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg">
                        <span class="block px-4 py-2 text-xs text-slate-500">Papel: {{ $currentRole->label() }}</span>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full px-4 py-2 text-left hover:bg-slate-50">Sair</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 p-4 lg:p-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if (! $currentRole->canWrite())
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">Você tem acesso somente leitura a este espaço.</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
