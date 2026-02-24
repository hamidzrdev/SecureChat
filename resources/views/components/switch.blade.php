@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
])

@php
    $switchId = $id ?? ($name ? str_replace(['.', '[', ']'], '-', $name) : 'switch-'.\Illuminate\Support\Str::random(8));
@endphp

<label for="{{ $switchId }}" class="flex cursor-pointer items-center justify-between gap-3">
    <span class="space-y-0.5">
        @if ($label)
            <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">{{ $label }}</span>
        @endif

        @if ($description)
            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $description }}</span>
        @endif
    </span>

    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
        <input
            id="{{ $switchId }}"
            name="{{ $name }}"
            type="checkbox"
            @checked($checked)
            {{ $attributes->class('peer sr-only') }}
        />
        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500 dark:bg-slate-700"></span>
        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
    </span>
</label>
