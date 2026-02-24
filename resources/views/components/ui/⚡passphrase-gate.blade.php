<?php

use Livewire\Component;

new class extends Component
{
    public string $contextTitle = '@noah_kim';

    public function mount(string $contextTitle = '@noah_kim'): void
    {
        $this->contextTitle = $contextTitle;
    }
};
?>

<div
    x-data="{
        passphrase: '',
        verifying: false,
        verified: false,
        errorMessage: '',
        verify() {
            this.errorMessage = '';
            this.verifying = true;

            setTimeout(() => {
                this.verifying = false;

                if (this.passphrase.trim() === 'rose-bridge') {
                    this.verified = true;
                    return;
                }

                this.errorMessage = 'Invalid passphrase. Please try again.';
            }, 850);
        },
    }"
    class="space-y-4"
>
    <section x-cloak x-show="!verified" x-transition>
        <div class="mx-auto max-w-lg">
            <x-card title="Passphrase Chat" description="Verify passphrase to enter the encrypted 1:1 thread.">
                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Passphrase is not saved. Re-enter after refresh.
                    </div>

                    <x-input
                        label="Passphrase"
                        name="passphrase"
                        type="password"
                        placeholder="Enter passphrase"
                        x-model="passphrase"
                    />

                    <div x-cloak x-show="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-500/50 dark:bg-rose-500/10 dark:text-rose-200">
                        <p class="font-medium" x-text="errorMessage"></p>
                    </div>

                    <x-button type="button" variant="primary" class="w-full" x-bind:disabled="verifying" x-on:click="verify()">
                        <svg
                            x-cloak
                            x-show="verifying"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" />
                            <path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" fill="none" />
                        </svg>
                        <span x-text="verifying ? 'Verifying...' : 'Verify passphrase'"></span>
                    </x-button>
                </div>
            </x-card>
        </div>
    </section>

    <section x-cloak x-show="verified" x-transition.opacity.duration.250ms>
        <livewire:ui.private-chat :context-title="$contextTitle" :passphrase-mode="true" />
    </section>
</div>
