<x-layouts.app title="Perfil de investidor">
    <x-page-header title="Perfil de investidor" subtitle="Usado para calcular a alocação-alvo e orientar o assistente de IA" />

    @php
        $current = old('target_allocation', $profile->target_allocation ?: \App\Enums\AssetClass::suggestedAllocation($profile->risk_profile ?? \App\Enums\RiskProfile::Moderate));
    @endphp

    <form method="POST" action="{{ route('investments.profile.update') }}" class="grid gap-6 lg:grid-cols-2"
          x-data="{
              alloc: @js(collect(\App\Enums\AssetClass::cases())->mapWithKeys(fn ($c) => [$c->value => (float) ($current[$c->value] ?? 0)])),
              suggestions: @js($suggestions),
              get total() { return Object.values(this.alloc).reduce((a, b) => a + (parseFloat(b) || 0), 0) },
              apply(profile) { const s = this.suggestions[profile]; Object.keys(this.alloc).forEach(k => this.alloc[k] = s[k] || 0) }
          }">
        @csrf @method('PUT')

        <div class="card space-y-4">
            <h2 class="font-semibold">Seu perfil</h2>
            <div>
                <label class="label">Perfil de risco</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (\App\Enums\RiskProfile::cases() as $rp)
                        <label class="cursor-pointer rounded-lg border p-3 text-center text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input type="radio" name="risk_profile" value="{{ $rp->value }}" class="sr-only" @checked(old('risk_profile', $profile->risk_profile?->value) === $rp->value)>
                            {{ $rp->label() }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-slate-500">Conservador prioriza segurança e liquidez; moderado equilibra; arrojado aceita mais oscilação buscando retorno maior no longo prazo.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label">Horizonte (anos)</label><input class="input" type="number" min="1" max="60" name="horizon_years" value="{{ old('horizon_years', $profile->horizon_years) }}"></div>
                <div><label class="label">Aporte mensal</label><input class="input" name="monthly_contribution" inputmode="decimal" value="{{ old('monthly_contribution', \App\Support\Money::input($profile->monthly_contribution)) }}"></div>
                <div><label class="label">Reserva (meses)</label><input class="input" type="number" min="0" max="36" name="emergency_fund_months" value="{{ old('emergency_fund_months', $profile->emergency_fund_months) }}"></div>
            </div>
            <div>
                <label class="label">Objetivos</label>
                <textarea class="input" name="objectives" rows="5" placeholder="Ex.: comprar imóvel em 5 anos, faculdade dos filhos, aposentar aos 55 com renda de R$ 15 mil...">{{ old('objectives', $profile->objectives) }}</textarea>
            </div>
        </div>

        <div class="card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">Alocação-alvo (%)</h2>
                <div class="flex gap-1 text-xs">
                    <span class="text-slate-500">Sugestão:</span>
                    @foreach (\App\Enums\RiskProfile::cases() as $rp)
                        <button type="button" class="rounded bg-slate-100 px-2 py-0.5 hover:bg-slate-200" @click="apply('{{ $rp->value }}')">{{ $rp->label() }}</button>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach (\App\Enums\AssetClass::cases() as $class)
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $class->color() }}"></span>
                        <label class="flex-1 text-sm">{{ $class->label() }}</label>
                        <input class="input w-20 text-right" type="number" step="0.5" min="0" max="100" name="target_allocation[{{ $class->value }}]" x-model.number="alloc['{{ $class->value }}']">
                    </div>
                @endforeach
            </div>
            <p class="text-sm" :class="Math.abs(total - 100) > 0.5 ? 'text-rose-600' : 'text-emerald-700'">Total: <strong x-text="total.toFixed(1) + '%'"></strong> <span x-show="Math.abs(total - 100) > 0.5">(precisa somar 100%)</span></p>
            <p class="text-xs text-slate-500">As sugestões são pontos de partida genéricos. Ajuste conforme seus objetivos — o assistente de IA pode ajudar.</p>
            @can('write')<button class="btn-primary w-full">Salvar perfil</button>@endcan
        </div>
    </form>
</x-layouts.app>
