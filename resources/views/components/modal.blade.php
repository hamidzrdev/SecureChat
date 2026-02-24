@props([
    'name' => 'modal',
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
    'show' => false,
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-3xl',
    ];

    $resolvedMaxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['lg'];
@endphp

<div
    x-data="{ open: @js((bool) $show) }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') { open = true }"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') { open = false }"
    x-on:keydown.escape.window="open = false"
    x-cloak
>
    <div x-show="open" class="fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" x-on:click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            class="relative w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-soft dark:border-slate-800 dark:bg-slate-900 {{ $resolvedMaxWidthClass }}"
        >
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    @if ($title)
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                    @endif

                    @if ($description)
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                    @endif
                </div>

                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                    x-on:click="open = false"
                >
                    <span class="sr-only">{{ __('chat.modal.close') }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
