@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'help' => null,
    'error' => null,
    'wrapperClass' => null,
])

@php
    $fieldId = $id ?? ($name ? str_replace(['.', '[', ']'], '-', $name) : 'field-'.\Illuminate\Support\Str::random(8));
    $isRtl = app()->isLocale('fa');

    $fieldError = $error;
    if (! $fieldError && $name && $errors->has($name)) {
        $fieldError = $errors->first($name);
    }

    $baseInputClasses = 'w-full rounded-xl border bg-white/90 px-3 py-2.5 text-sm text-slate-900 shadow-soft transition placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:bg-zinc-950/85 dark:text-brand-50 dark:placeholder:text-brand-200/45';
    $stateInputClasses = $fieldError
        ? 'border-rose-400/70 focus-visible:border-rose-400 focus-visible:ring-rose-400/70'
        : 'border-amber-300/90 focus-visible:border-brand-300 dark:border-brand-500/35';
@endphp

<div @class(['space-y-2', $wrapperClass])>
    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-slate-700 dark:text-brand-100">
            {{ $label }}
        </label>
    @endif

    @if ($type === 'password')
        <div x-data="{ reveal: false }" class="relative">
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="password"
                x-bind:type="reveal ? 'text' : 'password'"
                {{ $attributes->class("{$baseInputClasses} {$stateInputClasses} ".($isRtl ? 'pl-10' : 'pr-10')) }}
            />
            <button
                type="button"
                class="absolute inset-y-0 {{ $isRtl ? 'left-0' : 'right-0' }} flex items-center px-3 text-xs text-slate-500 transition hover:text-slate-700 dark:text-brand-200/65 dark:hover:text-brand-100"
                x-on:click="reveal = ! reveal"
            >
                <span x-text="reveal ? @js(__('chat.form.hide')) : @js(__('chat.form.show'))"></span>
            </button>
        </div>
    @elseif ($type === 'file')
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="file"
            {{ $attributes->class("{$baseInputClasses} {$stateInputClasses} file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700 hover:file:bg-brand-200 dark:file:bg-brand-500/22 dark:file:text-brand-100 dark:hover:file:bg-brand-500/30") }}
        />
    @else
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $attributes->class("{$baseInputClasses} {$stateInputClasses}") }}
        />
    @endif

    @if ($help && ! $fieldError)
        <p class="text-xs text-slate-500 dark:text-brand-200/60">{{ $help }}</p>
    @endif

    @if ($fieldError)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $fieldError }}</p>
    @endif
</div>
