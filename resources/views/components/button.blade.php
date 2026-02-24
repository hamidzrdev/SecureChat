@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 rounded-xl font-medium tracking-tight transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:pointer-events-none disabled:opacity-60 dark:focus-visible:ring-offset-zinc-950';

    $variantClasses = [
        'primary' => 'bg-gradient-to-b from-brand-300 to-brand-500 text-zinc-950 shadow-soft hover:from-brand-200 hover:to-brand-400',
        'secondary' => 'border border-amber-300/90 bg-white/90 text-slate-700 shadow-soft hover:border-brand-300 hover:bg-white dark:border-brand-500/35 dark:bg-zinc-900/90 dark:text-brand-100 dark:hover:border-brand-400/70 dark:hover:bg-zinc-900',
        'warning' => 'bg-gradient-to-b from-amber-300 to-amber-500 text-zinc-950 shadow-soft hover:from-amber-200 hover:to-amber-400',
        'ghost' => 'text-slate-600 hover:bg-amber-100/70 hover:text-slate-900 dark:text-brand-200 dark:hover:bg-brand-500/12 dark:hover:text-brand-100',
        'destructive' => 'bg-gradient-to-b from-rose-500 to-rose-600 text-rose-50 shadow-soft hover:from-rose-400 hover:to-rose-500',
    ];

    $sizeClasses = [
        'sm' => 'h-9 px-3 text-sm',
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-11 px-5 text-base',
    ];

    $resolvedVariantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
    $resolvedSizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<button type="{{ $type }}" @disabled($loading) {{ $attributes->class("{$baseClasses} {$resolvedVariantClass} {$resolvedSizeClass}") }}>
    @if ($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" />
            <path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" fill="none" />
        </svg>
    @endif

    <span>{{ $slot }}</span>
</button>
