@props(['value', 'colored' => true, 'type' => null])
@php
    $v = (float) $value;
    if ($type === 'expense') { $v = -abs($v); }
    $class = ! $colored ? '' : ($type === 'transfer' ? 'text-slate-500' : ($v < 0 ? 'text-rose-600' : ($v > 0 ? 'text-emerald-600' : 'text-slate-500')));
@endphp
<span {{ $attributes->merge(['class' => 'tabular-nums '.$class]) }}>{{ money($v) }}</span>
