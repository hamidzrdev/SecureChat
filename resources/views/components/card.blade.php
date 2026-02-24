@props([
    'title' => null,
    'description' => null,
    'padding' => 'md',
])

@php
    $paddingClasses = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5',
        'lg' => 'p-6',
    ];

    $resolvedPaddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];
@endphp

<section {{ $attributes->class("rounded-3xl border border-amber-200/80 bg-white/82 shadow-soft backdrop-blur-xl dark:border-brand-500/30 dark:bg-zinc-950/78 {$resolvedPaddingClass}") }}>
    @if ($title || $description || isset($actions))
        <header class="mb-4 flex items-start justify-between gap-3">
            <div class="space-y-1">
                @if ($title)
                    <h3 class="text-sm font-semibold tracking-tight text-slate-900 dark:text-brand-100">{{ $title }}</h3>
                @endif

                @if ($description)
                    <p class="text-xs leading-5 text-slate-500 dark:text-brand-200/65">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="shrink-0 pt-0.5">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}

    @isset($footer)
        <footer class="mt-4 border-t border-amber-200/70 pt-4 dark:border-brand-500/20">
            {{ $footer }}
        </footer>
    @endisset
</section>
