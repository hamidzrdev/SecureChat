@props([
    'name' => 'User',
    'src' => null,
    'size' => 'md',
])

@php
    $sizeClasses = [
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];

    $initials = collect(preg_split('/\s+/', trim($name) ?: 'U'))
        ->filter()
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $resolvedSizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->class("inline-flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 font-semibold text-white shadow-sm ring-1 ring-brand-300/50 dark:from-brand-300 dark:via-brand-500 dark:to-brand-700 dark:text-zinc-950 {$resolvedSizeClass}") }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="h-full w-full object-cover" />
    @else
        <span>{{ $initials }}</span>
    @endif
</div>
