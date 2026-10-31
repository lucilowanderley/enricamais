<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Entrar' }} · ENRICA+</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-900 via-brand-700 to-emerald-500 antialiased">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-white">
            <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-white/15 text-2xl font-bold">E+</div>
            <h1 class="text-3xl font-semibold tracking-tight">ENRICA<span class="text-emerald-200">+</span></h1>
            <p class="mt-1 text-sm text-emerald-50">Gestão financeira pessoal, familiar e empresarial</p>
        </div>
        <div class="rounded-2xl bg-white p-8 shadow-xl">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
