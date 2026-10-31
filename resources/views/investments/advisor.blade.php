<x-layouts.app title="Assistente de investimentos">
    <x-page-header title="Assistente de investimentos" subtitle="Orientações personalizadas com IA (Claude) a partir dos seus dados financeiros">
        <x-slot:actions>
            @if ($conversation)<a href="{{ route('investments.advisor') }}" class="btn-secondary">+ Nova conversa</a>@endif
        </x-slot:actions>
    </x-page-header>

    @unless ($configured)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-semibold">Configure a integração com a IA</p>
            <p class="mt-1">Crie uma chave em <a href="https://platform.claude.com/" target="_blank" rel="noopener" class="underline">platform.claude.com</a> e adicione ao arquivo <code class="rounded bg-amber-100 px-1">.env</code>:</p>
            <pre class="mt-2 overflow-x-auto rounded bg-amber-100 p-3 text-xs">ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-opus-5-5</pre>
            <p class="mt-2">Depois execute <code class="rounded bg-amber-100 px-1">php artisan config:clear</code>.</p>
        </div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="card h-fit p-3 lg:col-span-1">
            <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Conversas</p>
            <ul class="space-y-0.5 text-sm">
                @forelse ($conversations as $c)
                    <li>
                        <a href="{{ route('investments.advisor.show', $c) }}" class="block truncate rounded-lg px-2 py-1.5 {{ $conversation?->id === $c->id ? 'bg-brand-50 font-medium text-brand-700' : 'hover:bg-slate-50' }}" title="{{ $c->title }}">{{ $c->title }}</a>
                    </li>
                @empty
                    <li class="px-2 py-1.5 text-slate-400">Nenhuma conversa ainda.</li>
                @endforelse
            </ul>
        </aside>

        <section class="lg:col-span-3" x-data="{ loading: false }">
            @if (! $conversation)
                <div class="card mb-6">
                    <h2 class="mb-1 font-semibold">Análises rápidas</h2>
                    <p class="mb-4 text-sm text-slate-500">A IA recebe um resumo do seu fluxo de caixa, saldos, metas, perfil de investidor e carteira (sem descrições de lançamentos nem dados pessoais).</p>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ([
                            'diagnostico' => ['🩺', 'Diagnóstico completo', 'Saúde financeira e carteira'],
                            'rebalanceamento' => ['⚖️', 'Rebalanceamento', 'Onde colocar os próximos aportes'],
                            'reserva' => ['🛟', 'Reserva de emergência', 'Quanto ter e onde manter'],
                            'renda_fixa' => ['🏦', 'Renda fixa', 'Indexadores, prazos e liquidez'],
                            'aposentadoria' => ['🏖️', 'Independência financeira', 'Simulação de plano de longo prazo'],
                            'despesas' => ['✂️', 'Cortar despesas', 'Aumentar a capacidade de aporte'],
                        ] as $key => [$icon, $label, $desc])
                            <form method="POST" action="{{ route('investments.advisor.store') }}" @submit="loading = true">
                                @csrf
                                <input type="hidden" name="quick" value="{{ $key }}">
                                <button class="flex h-full w-full items-start gap-3 rounded-lg border border-slate-200 p-3 text-left text-sm hover:border-brand-500 hover:bg-brand-50 disabled:opacity-50" :disabled="loading" @disabled(! $configured)>
                                    <span class="text-xl">{{ $icon }}</span>
                                    <span><strong class="block text-slate-800">{{ $label }}</strong><span class="text-slate-500">{{ $desc }}</span></span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($conversation->messages as $m)
                        @if ($m->role === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-sm bg-brand-600 px-4 py-3 text-sm text-white">{{ $m->content }}</div>
                            </div>
                        @else
                            <div class="flex gap-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-emerald-400 text-sm text-white">✨</span>
                                <div class="card markdown min-w-0 flex-1 text-sm">{!! \Illuminate\Support\Str::markdown($m->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ $conversation ? route('investments.advisor.reply', $conversation) : route('investments.advisor.store') }}" class="card mt-6" @submit="loading = true">
                @csrf
                <textarea class="input" name="question" rows="3" required placeholder="{{ $conversation ? 'Faça uma pergunta de acompanhamento...' : 'Ex.: Tenho R$ 20 mil parados na conta corrente. Onde devo investir considerando meus objetivos?' }}" @disabled(! $configured)>{{ old('question') }}</textarea>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-400">Orientação educacional gerada por IA. Não substitui um profissional certificado; a decisão final é sua.</p>
                    <button class="btn-primary" :disabled="loading" @disabled(! $configured)>
                        <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
                        <span x-text="loading ? 'Analisando seus dados...' : 'Enviar'">Enviar</span>
                    </button>
                </div>
            </form>

            @if ($conversation)
                <form method="POST" action="{{ route('investments.advisor.destroy', $conversation) }}" class="mt-3 text-right" onsubmit="return confirm('Excluir esta conversa?')">
                    @csrf @method('DELETE')<button class="text-xs text-rose-600 hover:underline">Excluir conversa</button>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
