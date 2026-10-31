<x-layouts.guest title="Entrar">
    <h2 class="mb-6 text-xl font-semibold text-slate-900">Acesse sua conta</h2>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="email">E-mail</label>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>
        <div>
            <label class="label" for="password">Senha</label>
            <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300"> Manter conectado</label>
        <button class="btn-primary w-full">Entrar</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Ainda não tem conta? <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:underline">Cadastre-se grátis</a></p>
</x-layouts.guest>
