<x-layouts.guest title="Criar conta">
    <h2 class="mb-6 text-xl font-semibold text-slate-900">Crie sua conta</h2>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="name">Nome</label>
            <input class="input" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>
        <div>
            <label class="label" for="email">E-mail</label>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label" for="password">Senha</label>
                <input class="input" id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirmar</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
        </div>
        <button class="btn-primary w-full">Criar conta</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Já tem conta? <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Entrar</a></p>
</x-layouts.guest>
