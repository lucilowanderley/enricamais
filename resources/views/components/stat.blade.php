@props(['label', 'value', 'tone' => 'slate', 'hint' => null])
@php
    $tones = ['slate' => 'text-slate-900', 'green' => 'text-emerald-600', 'red' => 'text-rose-600', 'blue' => 'text-sky-600'];
@endphp
<div {{ $attributes->merge(['class' => 'card']) }}>
    <p class="text-sm text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold {{ $tones[$tone] ?? $tones['slate'] }}">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
    {{ $slot }}
</div>
