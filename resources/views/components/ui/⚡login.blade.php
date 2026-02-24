<?php

use Livewire\Component;

new class extends Component
{
    public int $ttlMinutes;

    public array $protectedIds = [
        'support',
        'ops',
        'admin',
        'release-room',
    ];

    public function mount(): void
    {
        $this->ttlMinutes = (int) config('chat.ttl_minutes', 120);
    }
};
?>

<div
    x-data="{
        chatId: @js(old('chat_id', '')),
        setPassword: @js((bool) old('set_password', false)),
        requiresPassword: false,
        password: '',
        confirmPassword: '',
        errors: {},
        submitting: false,
        protectedIds: @js($protectedIds),
        refreshProtectionState() {
            const normalized = this.chatId.trim().toLowerCase();
            this.requiresPassword = this.protectedIds.includes(normalized);
        },
        submit() {
            this.errors = {};
            this.refreshProtectionState();

            if (! this.chatId.trim()) {
                this.errors.chatId = 'Chat ID is required.';
            }

            if (this.setPassword) {
                if (! this.password) {
                    this.errors.password = 'Password is required.';
                }

                if (this.password.length > 0 && this.password.length < 6) {
                    this.errors.password = 'Password must be at least 6 characters.';
                }

                if (this.confirmPassword !== this.password) {
                    this.errors.confirmPassword = 'Passwords do not match.';
                }
            }

            if (! this.setPassword && this.requiresPassword && ! this.password) {
                this.errors.password = 'This ID is protected. Enter its password.';
            }

            if (Object.keys(this.errors).length > 0) {
                return;
            }

            this.submitting = true;
            this.$refs.loginForm.submit();
        },
    }"
    x-init="refreshProtectionState()"
    class="mx-auto w-full max-w-xl"
>
    <x-card padding="lg" class="overflow-hidden">
        <header class="mb-6 space-y-3">
            <x-badge variant="primary">Secure Login</x-badge>

            <div class="rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-sm text-brand-900 dark:border-brand-500/40 dark:bg-brand-500/15 dark:text-brand-100">
                <p class="font-semibold">Messages are deleted after {{ $ttlMinutes }} minutes.</p>
                <p class="mt-1 text-xs opacity-90">Retention policy is configurable via `CHAT_TTL_MINUTES`.</p>
            </div>

            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Sign in to your chat</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Claim your Chat ID and continue securely.</p>
            </div>
        </header>

        @if ($errors->has('chat_id') || $errors->has('password'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-500/50 dark:bg-rose-500/10 dark:text-rose-200">
                <p class="font-medium">Login failed. Check your Chat ID / password and try again.</p>
            </div>
        @endif

        <form x-ref="loginForm" method="POST" action="{{ route('chat.login.store') }}" class="space-y-4" x-on:submit.prevent="submit()">
            @csrf

            <div>
                <x-input
                    label="Chat ID"
                    name="chat_id"
                    :value="old('chat_id')"
                    placeholder="e.g. support-room"
                    help="3-32 chars, allowed: letters, numbers, dot, underscore, dash."
                    x-model="chatId"
                    x-on:input="refreshProtectionState()"
                    x-bind:class="errors.chatId ? 'border-rose-400 focus-visible:border-rose-500 focus-visible:ring-rose-400 dark:border-rose-500/80' : ''"
                />
                <p x-show="errors.chatId" x-text="errors.chatId" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300"></p>
            </div>

            <x-switch
                name="set_password"
                label="Set a password for this ID"
                description="Recommended for IDs you want to keep private."
                x-model="setPassword"
            />

            <template x-if="setPassword">
                <div class="space-y-4">
                    <div>
                        <x-input
                            label="Password"
                            name="password"
                            type="password"
                            placeholder="Choose a strong password"
                            x-model="password"
                            x-bind:class="errors.password ? 'border-rose-400 focus-visible:border-rose-500 focus-visible:ring-rose-400 dark:border-rose-500/80' : ''"
                        />
                        <p x-show="errors.password" x-text="errors.password" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300"></p>
                    </div>

                    <div>
                        <x-input
                            label="Confirm password"
                            name="password_confirmation"
                            type="password"
                            placeholder="Re-enter your password"
                            x-model="confirmPassword"
                            x-bind:class="errors.confirmPassword ? 'border-rose-400 focus-visible:border-rose-500 focus-visible:ring-rose-400 dark:border-rose-500/80' : ''"
                        />
                        <p x-show="errors.confirmPassword" x-text="errors.confirmPassword" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300"></p>
                    </div>
                </div>
            </template>

            <template x-if="!setPassword && requiresPassword">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/50 dark:bg-amber-500/10">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-200">This ID already requires a password.</p>
                    <p class="mt-1 text-xs text-amber-700/90 dark:text-amber-200/80">Enter the existing password to continue.</p>

                    <div class="mt-3">
                        <x-input
                            label="Password"
                            name="password"
                            type="password"
                            placeholder="Enter ID password"
                            x-model="password"
                            x-bind:class="errors.password ? 'border-rose-400 focus-visible:border-rose-500 focus-visible:ring-rose-400 dark:border-rose-500/80' : ''"
                        />
                        <p x-show="errors.password" x-text="errors.password" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300"></p>
                    </div>
                </div>
            </template>

            <p class="rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                If you do not set a password, someone else may claim this ID.
            </p>

            <x-button type="submit" variant="primary" size="lg" class="w-full" x-bind:disabled="submitting">
                <svg
                    x-cloak
                    x-show="submitting"
                    class="h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" />
                    <path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" fill="none" />
                </svg>
                <span x-text="submitting ? 'Connecting...' : 'Enter chat'"></span>
            </x-button>
        </form>
    </x-card>
</div>
