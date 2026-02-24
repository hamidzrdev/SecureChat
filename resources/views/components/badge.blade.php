@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variantClasses = [
        'primary' => 'bg-brand-100/80 text-brand-800 ring-brand-200 dark:bg-brand-500/20 dark:text-brand-100 dark:ring-brand-400/45',
        'secondary' => 'bg-white/90 text-slate-700 ring-amber-200 dark:bg-zinc-800/80 dark:text-zinc-100 dark:ring-zinc-700',
        'success' => 'bg-emerald-100/90 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/18 dark:text-emerald-200 dark:ring-emerald-400/35',
        'warning' => 'bg-amber-100/90 text-amber-800 ring-amber-200 dark:bg-amber-500/18 dark:text-amber-100 dark:ring-amber-400/40',
        'danger' => 'bg-rose-100/90 text-rose-700 ring-rose-200 dark:bg-rose-500/18 dark:text-rose-200 dark:ring-rose-400/35',
        'neutral' => 'bg-white/85 text-slate-700 ring-amber-200 dark:bg-zinc-900/72 dark:text-zinc-200 dark:ring-zinc-700/90',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    $resolvedVariantClass = $variantClasses[$variant] ?? $variantClasses['neutral'];
    $resolvedSizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1 rounded-full font-medium tracking-tight ring-1 ring-inset {$resolvedVariantClass} {$resolvedSizeClass}") }}>
    {{ $slot }}
</span>
