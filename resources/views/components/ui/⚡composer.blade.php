<?php

use Livewire\Component;

new class extends Component
{
    public string $context = 'public';

    public function mount(string $context = 'public'): void
    {
        $this->context = $context;
    }
};
?>

<div
    x-data="{
        openEmoji: false,
        draft: '',
        sending: false,
        fileName: '',
        send() {
            if (! this.draft.trim() && ! this.fileName) {
                return;
            }

            this.sending = true;

            setTimeout(() => {
                this.sending = false;
                this.draft = '';
                this.fileName = '';
                this.$refs.fileInput.value = '';
            }, 900);
        },
    }"
    x-on:composer-emoji-picked.window="draft += $event.detail.emoji; openEmoji = false"
    class="space-y-2"
>
    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-soft transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-200 dark:border-slate-800 dark:bg-slate-900 dark:focus-within:border-brand-500 dark:focus-within:ring-brand-500/30">
        <div class="relative flex items-end gap-2">
            <div class="relative">
                <x-button
                    type="button"
                    size="sm"
                    variant="ghost"
                    x-on:click="openEmoji = ! openEmoji"
                    x-bind:aria-expanded="openEmoji.toString()"
                >
                    😊
                </x-button>

                <div x-cloak x-show="openEmoji" x-on:click.outside="openEmoji = false" class="absolute bottom-11 left-0 z-30">
                    <livewire:ui.emoji-picker-popover />
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-9 items-center justify-center rounded-xl px-2.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                x-on:click="$refs.fileInput.click()"
            >
                <span class="sr-only">Upload image</span>
                <svg class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M10 4v8m0-8l-3 3m3-3 3 3" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4 12.5A3.5 3.5 0 0 0 7.5 16h5a3.5 3.5 0 0 0 3.5-3.5" stroke-linecap="round" />
                </svg>
            </button>

            <input
                x-ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp,image/gif"
                class="hidden"
                x-on:change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
            />

            <label for="composer-message" class="sr-only">Write a message</label>
            <textarea
                id="composer-message"
                rows="1"
                x-model="draft"
                class="max-h-36 min-h-10 flex-1 resize-y rounded-xl border border-transparent bg-slate-100 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-0 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                placeholder="Type a message..."
            ></textarea>

            <x-button type="button" variant="primary" size="sm" x-bind:disabled="sending" x-on:click="send()">
                <svg
                    x-cloak
                    x-show="sending"
                    class="h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" />
                    <path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" fill="none" />
                </svg>
                <span x-text="sending ? 'Sending...' : 'Send'"></span>
            </x-button>
        </div>

        <div x-cloak x-show="fileName" class="mt-2">
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M5 5.5A2.5 2.5 0 0 1 7.5 3h5A2.5 2.5 0 0 1 15 5.5v9A2.5 2.5 0 0 1 12.5 17h-5A2.5 2.5 0 0 1 5 14.5v-9Z" />
                </svg>
                <span x-text="fileName"></span>
                <button type="button" class="rounded-full p-0.5 hover:bg-slate-200 dark:hover:bg-slate-700" x-on:click="fileName = ''; $refs.fileInput.value = ''">
                    <span class="sr-only">Remove file</span>
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
                    </svg>
                </button>
            </span>
        </div>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400">
        Allowed formats: JPG, PNG, WEBP, GIF. Max file size: 8MB (placeholder).
    </p>
</div>
