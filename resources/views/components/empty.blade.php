@props(['message'])
<div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
    {{ $message }}
    @if (! $slot->isEmpty())<div class="mt-3">{{ $slot }}</div>@endif
</div>
