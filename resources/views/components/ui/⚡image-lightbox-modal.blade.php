<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div
    x-data="{ open: false, image: { src: '', caption: '', author: '', time: '' } }"
    x-on:open-image-lightbox.window="image = $event.detail; open = true"
    x-on:keydown.escape.window="open = false"
    x-cloak
>
    <div x-show="open" class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-8" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" x-on:click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="relative w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-950 shadow-soft"
        >
            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100" x-text="image.caption || 'Image preview'"></p>
                    <p class="mt-0.5 text-xs text-slate-400">
                        <span x-text="image.author || 'Unknown'"></span>
                        <span> - </span>
                        <span x-text="image.time || '--:--'"></span>
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition hover:bg-slate-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    x-on:click="open = false"
                >
                    <span class="sr-only">Close image preview</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <div class="max-h-[75vh] overflow-auto bg-black p-3 sm:p-4">
                <img x-bind:src="image.src" x-bind:alt="image.caption || 'Lightbox image'" class="mx-auto h-auto max-h-[68vh] w-auto rounded-xl object-contain" />
            </div>
        </div>
    </div>
</div>
