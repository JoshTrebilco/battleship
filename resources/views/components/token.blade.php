@props([
    'variant' => 'ship',
    'size' => 48,
    'label' => null,
])

@php
    $gradients = [
        'ship' => 'from-sky-500 via-sky-600 to-blue-700',
        'hit' => 'from-rose-500 via-amber-500 to-yellow-400',
        'miss' => 'from-slate-500 via-slate-600 to-slate-700',
        'pending' => 'from-emerald-400 via-emerald-500 to-teal-500',
        'unknown' => 'from-slate-600 via-slate-700 to-slate-800',
    ];

    $gradient = $gradients[$variant] ?? $gradients['ship'];
@endphp

<div
    class="relative inline-flex items-center justify-center rounded-full text-white font-semibold shadow-lg shadow-black/10"
    style="width: {{ $size }}px; height: {{ $size }}px;"
>
    <div class="w-full h-full rounded-full bg-linear-to-br {{ $gradient }} border border-white/10 flex items-center justify-center text-xs uppercase tracking-wide">
        {{ $label }}
    </div>
</div>

