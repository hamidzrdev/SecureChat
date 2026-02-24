@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'duration' => 4200,
    'autohide' => true,
])

@php
    $typeClasses = [
        'info' => 'border-brand-200 bg-brand-50/90 text-brand-900 dark:border-brand-500/40 dark:bg-brand-500/20 dark:text-brand-100',
        'success' => 'border-emerald-200 bg-emerald-50/90 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/20 dark:text-emerald-100',
        'warning' => 'border-amber-200 bg-amber-50/90 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/20 dark:text-amber-100',
        'danger' => 'border-rose-200 bg-rose-50/90 text-rose-900 dark:border-rose-500/40 dark:bg-rose-500/20 dark:text-rose-100',
    ];

    $resolvedTypeClass = $typeClasses[$type] ?? $typeClasses['info'];
@endphp

<article
    x-data="{ visible: true }"
    x-init="@if($autohide) setTimeout(() => visible = false, {{ $duration }}); @endif"
    x-show="visible"
    x-transition:enter="transition ease-out duration-250"
    x-transition:enter-start="translate-y-2 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-180"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="pointer-events-auto rounded-xl border p-3 shadow-soft backdrop-blur {{ $resolvedTypeClass }}"
>
    <div class="flex items-start justify-between gap-3">
        <div>
            @if ($title)
                <p class="text-sm font-semibold">{{ $title }}</p>
            @endif

            @if ($message)
                <p class="mt-1 text-xs opacity-90">{{ $message }}</p>
            @endif
        </div>

        <button type="button" class="rounded-md p-1 opacity-70 transition hover:opacity-100" x-on:click="visible = false">
            <span class="sr-only">{{ __('chat.toast.dismiss') }}</span>
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
            </svg>
        </button>
    </div>
</article>
