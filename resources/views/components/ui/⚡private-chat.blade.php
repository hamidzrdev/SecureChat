<?php

use Livewire\Component;

new class extends Component
{
    public string $contextTitle = '@noah_kim';

    public bool $passphraseMode = false;

    public function mount(string $contextTitle = '@noah_kim', bool $passphraseMode = false): void
    {
        $this->contextTitle = $contextTitle;
        $this->passphraseMode = $passphraseMode;
    }
};
?>

<div class="space-y-4">
    <x-card padding="none" class="overflow-hidden">
        <header class="border-b border-slate-200 p-4 sm:p-5 dark:border-slate-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $contextTitle }}</h2>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge variant="secondary">Private chat</x-badge>
                        <x-badge variant="success">Images enabled</x-badge>
                        @if ($passphraseMode)
                            <x-badge variant="warning">Passphrase mode</x-badge>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="button" variant="ghost" size="sm" x-on:click="$dispatch('toggle-sidebar')">
                        Users
                    </x-button>

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <span class="sr-only">Search messages</span>
                        <svg class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="9" cy="9" r="5" />
                            <path d="M13 13l4 4" stroke-linecap="round" />
                        </svg>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-1 rounded-xl border border-slate-300 px-2.5 text-sm text-slate-600 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            x-on:click="open = ! open"
                            x-bind:aria-expanded="open.toString()"
                        >
                            Settings
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            x-on:click.outside="open = false"
                            x-transition
                            class="absolute right-0 z-20 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-1 shadow-soft dark:border-slate-700 dark:bg-slate-900"
                        >
                            <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">Clear history (UI)</button>
                            <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">Export transcript</button>
                            <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/15">Close thread</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <livewire:ui.message-list mode="private" state="filled" />

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 lg:static lg:border-t lg:bg-transparent lg:p-4 lg:backdrop-blur-none lg:dark:bg-transparent">
            <livewire:ui.composer context="private" />
        </div>
    </x-card>

    <livewire:ui.image-lightbox-modal />
</div>
