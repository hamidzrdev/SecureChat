<div class="mx-auto w-full max-w-xl">
    <x-card padding="lg" class="overflow-hidden">
        <header class="mb-6 space-y-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-brand-100">{{ __('chat.login.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.login.subtitle') }}</p>
            </div>
        </header>

        <form class="space-y-4" wire:submit="authenticate">
            <div>
                <x-input
                    :label="__('chat.login.chat_id_label')"
                    name="chat_id"
                    wire:model.live.debounce.300ms="chatId"
                    :placeholder="__('chat.login.chat_id_placeholder')"
                    :help="__('chat.login.chat_id_help')"
                />
                @error('chatId')
                    <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 rounded-xl border border-amber-200/90 bg-white/90 px-3 py-2 text-sm text-slate-700 dark:border-brand-500/30 dark:bg-zinc-900/55 dark:text-brand-100">
                <input type="checkbox" wire:model.live="setPassword" class="rounded border-amber-300 text-brand-500 focus:ring-brand-300 dark:border-brand-500/60 dark:bg-zinc-950" />
                <span>{{ __('chat.login.set_password_toggle') }}</span>
            </label>

            @if ($setPassword)
                <div class="space-y-4">
                    <div>
                        <x-input
                            :label="__('chat.login.password_label')"
                            name="password"
                            type="password"
                            wire:model.defer="password"
                            :placeholder="__('chat.login.password_placeholder')"
                        />
                        @error('password')
                            <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-input
                            :label="__('chat.login.password_confirmation_label')"
                            name="password_confirmation"
                            type="password"
                            wire:model.defer="passwordConfirmation"
                            :placeholder="__('chat.login.password_confirmation_placeholder')"
                        />
                        @error('passwordConfirmation')
                            <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @elseif ($requiresPassword)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-brand-500/35 dark:bg-brand-500/14">
                    <p class="text-xs font-medium text-amber-900 dark:text-brand-100">{{ __('chat.login.password_required_notice') }}</p>
                    <div class="mt-3">
                        <x-input
                            :label="__('chat.login.password_label')"
                            name="password_existing"
                            type="password"
                            wire:model.defer="password"
                            :placeholder="__('chat.login.password_existing_placeholder')"
                        />
                    </div>
                    @error('password')
                        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <p class="rounded-lg border border-amber-200/90 bg-white/90 px-3 py-2 text-xs text-slate-500 dark:border-brand-500/25 dark:bg-zinc-900/65 dark:text-brand-200/70">
                {{ __('chat.login.security_copy') }}
            </p>

            <x-button type="submit" variant="primary" size="lg" class="w-full">
                <span wire:loading.remove wire:target="authenticate">{{ __('chat.login.submit') }}</span>
                <span wire:loading wire:target="authenticate">{{ __('chat.login.loading') }}</span>
            </x-button>
        </form>
    </x-card>
</div>

