@props(['month', 'route', 'params' => []])
<div class="inline-flex items-center rounded-lg border border-slate-300 bg-white text-sm shadow-sm">
    <a href="{{ route($route, array_merge($params, ['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')])) }}" class="px-3 py-2 hover:bg-slate-50" aria-label="Mês anterior">‹</a>
    <span class="min-w-32 border-x border-slate-200 px-3 py-2 text-center font-medium">{{ ucfirst($month->translatedFormat('F Y')) }}</span>
    <a href="{{ route($route, array_merge($params, ['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')])) }}" class="px-3 py-2 hover:bg-slate-50" aria-label="Próximo mês">›</a>
</div>
