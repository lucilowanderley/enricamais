@props(['percent', 'color' => null])
@php
    $p = max(0, (float) $percent);
    $bar = $color ?? ($p >= 100 ? '#e11d48' : ($p >= 80 ? '#f59e0b' : '#16a34a'));
@endphp
<div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
    <div class="h-full rounded-full" style="width: {{ min(100, $p) }}%; background: {{ $bar }}"></div>
</div>
