<div
    class="flex h-full min-h-0 flex-col gap-2"
    @if ($mode === 'public')
        wire:poll.15s="pollMessages"
    @endif
    x-data="{
        _observer: null,
        _observedTimeline: null,
        _shouldRefocusComposer: false,
        _cleanupMorphUpdatingHook: null,
        _cleanupMorphedHook: null,
        init() {
            this.bindTimelineObserver();
            this.$nextTick(() => {
                this.restoreComposerDraft();
                this.scrollToBottom();
            });

            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                this.registerMorphedHook();
            } else {
                document.addEventListener('livewire:init', () => this.registerMorphedHook(), { once: true });
            }
        },
        destroy() {
            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }

            this._observedTimeline = null;

            if (typeof this._cleanupMorphedHook === 'function') {
                this._cleanupMorphedHook();
                this._cleanupMorphedHook = null;
            }

            if (typeof this._cleanupMorphUpdatingHook === 'function') {
                this._cleanupMorphUpdatingHook();
                this._cleanupMorphUpdatingHook = null;
            }
        },
        registerMorphedHook() {
            if (
                (this._cleanupMorphedHook && this._cleanupMorphUpdatingHook)
                || !window.Livewire
                || typeof window.Livewire.hook !== 'function'
            ) {
                return;
            }

            const componentId = this.$root.closest('[wire\\:id]')?.getAttribute('wire:id') ?? null;

            this._cleanupMorphUpdatingHook = window.Livewire.hook('morph.updating', ({ component }) => {
                if (componentId && component?.id !== componentId) {
                    return;
                }

                const activeElement = document.activeElement;
                this._shouldRefocusComposer = Boolean(
                    activeElement
                    && this.$root.contains(activeElement)
                    && activeElement.matches?.('[data-chat-composer-input]')
                );
            });

            this._cleanupMorphedHook = window.Livewire.hook('morphed', ({ component }) => {
                if (componentId && component?.id !== componentId) {
                    return;
                }

                this.bindTimelineObserver();
                this.restoreComposerDraft();
                this.scrollToBottom();
                if (this._shouldRefocusComposer) {
                    this.focusComposer();
                    this._shouldRefocusComposer = false;
                }
            });
        },
        bindTimelineObserver() {
            const timeline = this.$refs.timeline;
            if (!timeline || this._observedTimeline === timeline) {
                return;
            }

            if (this._observer) {
                this._observer.disconnect();
            }

            this._observedTimeline = timeline;
            this._observer = new MutationObserver(() => this.scrollToBottom());
            this._observer.observe(timeline, { childList: true, subtree: true });
        },
        focusComposer(retry = 4) {
            const composerInput = Array.from(this.$root.querySelectorAll('[data-chat-composer-input]'))
                .find((el) => !el.disabled && el.offsetParent !== null);

            if (!composerInput) {
                if (retry > 0) {
                    requestAnimationFrame(() => this.focusComposer(retry - 1));
                }

                return;
            }

            requestAnimationFrame(() => {
                composerInput.focus({ preventScroll: true });

                if (typeof composerInput.setSelectionRange === 'function') {
                    const end = (composerInput.value || '').length;
                    composerInput.setSelectionRange(end, end);
                }
            });
        },
        restoreComposerDraft() {
            const composerInput = Array.from(this.$root.querySelectorAll('[data-chat-composer-input]'))
                .find((el) => !el.disabled && el.offsetParent !== null);

            if (!composerInput || (composerInput.value ?? '') !== '') {
                return;
            }

            const normalDraft = sessionStorage.getItem(@js('schat-draft-'.$context.'-'.((int) $conversationId))) ?? '';
            const passphraseDraft = sessionStorage.getItem(@js('schat-passphrase-draft-'.((int) $conversationId))) ?? '';
            const cachedDraft = normalDraft !== '' ? normalDraft : passphraseDraft;

            if (cachedDraft === '') {
                return;
            }

            const keepCaret = document.activeElement === composerInput;
            composerInput.value = cachedDraft;
            composerInput.dispatchEvent(new Event('input', { bubbles: true }));

            if (keepCaret && typeof composerInput.setSelectionRange === 'function') {
                composerInput.setSelectionRange(cachedDraft.length, cachedDraft.length);
            }
        },
        clearComposerDraftCache() {
            sessionStorage.removeItem(@js('schat-draft-'.$context.'-'.((int) $conversationId)));
            sessionStorage.removeItem(@js('schat-passphrase-draft-'.((int) $conversationId)));

            this.$root.querySelectorAll('[data-chat-composer-input]').forEach((input) => {
                if (!input || (input.value ?? '') === '') {
                    return;
                }

                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        },
        scrollToBottom(retry = 3) {
            this.bindTimelineObserver();

            const timeline = this.$refs.timeline;
            if (!timeline) {
                if (retry > 0) {
                    requestAnimationFrame(() => this.scrollToBottom(retry - 1));
                }

                return;
            }

            requestAnimationFrame(() => {
                timeline.scrollTop = timeline.scrollHeight;
            });
        },
    }"
    x-on:conversation-scroll-to-bottom.window="if (($event.detail?.context ?? '') === @js($context)) { scrollToBottom(); if ($event.detail?.clearComposerDraft) { clearComposerDraftCache(); } if ($event.detail?.focusComposer) { focusComposer(); } }"
    x-on:conversation-force-refresh.main.window="scrollToBottom()"
    x-on:conversation-force-refresh.modal.window="scrollToBottom()"
>
    @php
        $ttlMinutes = (int) config('chat.ttl_minutes', 120);
        $isPublicMode = $mode === 'public';
        $headerTitle = $isPublicMode
            ? __('chat.chat_shell.public_room')
            : ($conversationId ? __('chat.conversation.title_with_id', ['id' => $conversationId]) : __('chat.conversation.title'));
        $headerDescription = $isPublicMode
            ? null
            : ($passphraseMode ? __('chat.conversation.passphrase_mode_description') : __('chat.conversation.normal_mode_description'));
        $autoDeleteBadgeText = app()->isLocale('fa')
            ? 'حذف خودکار بعد از '.$ttlMinutes.' دقیقه'
            : 'Auto delete after '.$ttlMinutes.' minutes';
    @endphp

    <x-card
        :title="$headerTitle"
        :description="$headerDescription"
        class="flex min-h-0 flex-1 flex-col overflow-hidden border border-amber-200/80 bg-white/84 shadow-soft backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/82 dark:backdrop-blur-xl"
    >
        @if ($isPublicMode)
            <x-slot:actions>
                <x-badge variant="warning" size="sm">{{ $autoDeleteBadgeText }}</x-badge>
            </x-slot:actions>
        @endif

        @if (! $conversationId)
            <div class="flex h-full items-center justify-center rounded-xl border border-dashed border-amber-300/80 bg-amber-50/60 p-6 text-center dark:border-brand-500/30 dark:bg-zinc-900/60">
                <p class="text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.conversation.select_from_sidebar') }}</p>
            </div>
        @else
            <div x-ref="timeline" class="min-h-0 flex-1 space-y-3 overflow-y-auto rounded-2xl border border-amber-200/80 bg-gradient-to-b from-white to-amber-50 p-3 pr-2 dark:border-brand-500/24 dark:from-zinc-950 dark:to-zinc-900">
                @forelse ($messages as $message)
                    @php
                        $isOwnMessage = (int) ($message['sender_id'] ?? 0) === (int) auth()->id();
                        $senderChatId = trim((string) ($message['sender_chat_id'] ?? ''));
                    @endphp

                    <div wire:key="message-{{ $message['id'] }}" @class(['flex', 'justify-end' => $isOwnMessage])>
                        <article
                            @class([
                                'max-w-[82%] rounded-2xl border px-3.5 py-3 shadow-sm transition',
                                'border-brand-300/70 bg-brand-50/90 ring-1 ring-brand-200/70 dark:border-brand-400/55 dark:bg-brand-500/16 dark:ring-brand-400/30' => $isOwnMessage,
                                'border-amber-200/80 bg-white/92 ring-1 ring-amber-100/80 dark:border-brand-500/24 dark:bg-zinc-900/92 dark:ring-zinc-800/90' => ! $isOwnMessage,
                            ])
                        >
                            <div class="mb-2 flex items-center justify-between gap-3 text-[11px] text-slate-500 dark:text-brand-200/55">
                                <span>
                                    {{ $isOwnMessage ? __('chat.conversation.you') : ($senderChatId !== '' ? '@'.$senderChatId : __('chat.conversation.user_with_id', ['id' => $message['sender_id'] ?? '-'])) }}
                                </span>
                                <span>{{ $message['created_at_relative'] ?? ($message['created_at'] ?? '-') }}</span>
                            </div>

                            @if (isset($message['plaintext']['text']))
                                <p class="whitespace-pre-wrap text-sm leading-6 text-slate-800 dark:text-brand-50">{{ $message['plaintext']['text'] }}</p>
                            @elseif (isset($message['ciphertext_base64']))
                                <p
                                    class="break-words rounded-xl border border-amber-300 bg-amber-50 px-3 py-2.5 text-sm text-amber-900 dark:border-amber-400/45 dark:bg-amber-500/14 dark:text-amber-100"
                                    x-data="window.SChatPassphrase.messageVM({
                                        messageId: @js((int) ($message['id'] ?? 0)),
                                        conversationId: @js((int) $conversationId),
                                        ciphertextBase64: @js((string) $message['ciphertext_base64']),
                                        cryptoMeta: @js($message['crypto_meta'] ?? []),
                                    })"
                                    x-init="init()"
                                    x-on:schat-passphrase-updated.window="if ($event.detail.conversationId === conversationId) { decrypt(); }"
                                    x-text="text"
                                ></p>
                            @endif

                            @if (($message['kind'] ?? null) === 'image' && isset($message['attachment_meta']['access_url']))
                                <div class="mt-2">
                                    <a
                                        href="{{ $message['attachment_meta']['access_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group block w-fit overflow-hidden rounded-xl border border-amber-300 bg-white shadow-soft transition hover:-translate-y-0.5 hover:border-brand-300 dark:border-brand-500/35 dark:bg-zinc-900 dark:hover:border-brand-400/70"
                                    >
                                        <img
                                            src="{{ $message['attachment_meta']['access_url'] }}"
                                            alt="{{ __('chat.conversation.open_image') }}"
                                            loading="lazy"
                                            class="block max-h-72 w-auto max-w-[min(70vw,22rem)] object-cover sm:max-w-sm"
                                        />
                                    </a>
                                </div>
                            @endif
                        </article>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center rounded-xl border border-dashed border-amber-300/80 bg-amber-50/60 p-6 text-center dark:border-brand-500/30 dark:bg-zinc-900/60">
                        <p class="text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.conversation.no_messages') }}</p>
                    </div>
                @endforelse

                <div x-ref="timelineBottom" class="h-px w-full" aria-hidden="true"></div>
            </div>
        @endif
    </x-card>

    @if ($conversationId)
        <div class="mt-auto sticky bottom-0 z-20 rounded-2xl border border-amber-200/80 bg-white/92 p-2 shadow-soft ring-1 ring-amber-100/70 backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/92 dark:ring-brand-500/20 dark:backdrop-blur-xl">
            <div class="space-y-2">
                @if ($passphraseMode)
                    <div
                        class="space-y-3 rounded-2xl border border-amber-200 bg-amber-50/90 p-3 dark:border-brand-400/35 dark:bg-brand-500/14"
                        x-data="window.SChatPassphrase.composer({
                            conversationId: @js((int) $conversationId),
                            context: @js($context),
                            configured: @js((bool) $passphraseConfigured),
                            iter: @js((int) config('chat.passphrase.kdf_iter', 150000)),
                            algo: @js((string) config('chat.passphrase.algo', 'AES-GCM')),
                            kdf: @js((string) config('chat.passphrase.kdf', 'PBKDF2')),
                        })"
                        x-init="init()"
                    >
                        <template x-if="!unlocked">
                            <div class="space-y-3">
                                <p class="text-xs font-medium text-slate-700 dark:text-brand-100">
                                    {{ __('chat.conversation.passphrase_enter_notice') }}
                                </p>

                                <label class="block text-sm font-medium text-slate-700 dark:text-brand-100">{{ __('chat.common.passphrase') }}</label>
                                <div class="relative">
                                    <input
                                        x-bind:type="revealPassphrase ? 'text' : 'password'"
                                        x-model="passphrase"
                                        class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                        placeholder="{{ __('chat.conversation.passphrase_placeholder') }}"
                                    />
                                    <button
                                        type="button"
                                        x-on:click="revealPassphrase = !revealPassphrase"
                                        class="absolute {{ app()->isLocale('fa') ? 'left-2' : 'right-2' }} top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-xs text-slate-500 transition hover:bg-amber-100 hover:text-slate-700 dark:text-brand-200/70 dark:hover:bg-brand-500/12 dark:hover:text-brand-100"
                                    >
                                        <span x-text="revealPassphrase ? @js(__('chat.form.hide')) : @js(__('chat.form.show'))"></span>
                                    </button>
                                </div>

                                <div x-show="!configured">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-brand-100">{{ __('chat.conversation.confirm_passphrase') }}</label>
                                    <div class="relative mt-1">
                                        <input
                                            x-bind:type="revealConfirmPassphrase ? 'text' : 'password'"
                                            x-model="confirmPassphrase"
                                            class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                            placeholder="{{ __('chat.conversation.confirm_passphrase_placeholder') }}"
                                        />
                                        <button
                                            type="button"
                                            x-on:click="revealConfirmPassphrase = !revealConfirmPassphrase"
                                            class="absolute {{ app()->isLocale('fa') ? 'left-2' : 'right-2' }} top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-xs text-slate-500 transition hover:bg-amber-100 hover:text-slate-700 dark:text-brand-200/70 dark:hover:bg-brand-500/12 dark:hover:text-brand-100"
                                        >
                                            <span x-text="revealConfirmPassphrase ? @js(__('chat.form.hide')) : @js(__('chat.form.show'))"></span>
                                        </button>
                                    </div>
                                </div>

                                <x-button type="button" variant="warning" x-bind:disabled="working" x-on:click="unlock()">
                                    <span x-show="!working && !configured">{{ __('chat.conversation.set_passphrase') }}</span>
                                    <span x-show="!working && configured">{{ __('chat.conversation.unlock_chat') }}</span>
                                    <span x-show="working">{{ __('chat.conversation.checking') }}</span>
                                </x-button>
                            </div>
                        </template>

                        <template x-if="unlocked">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ __('chat.conversation.passphrase_verified') }}</p>
                                    <x-button type="button" size="sm" variant="ghost" x-on:click="lock()">{{ __('chat.conversation.lock') }}</x-button>
                                </div>

                                <textarea
                                    data-chat-composer-input
                                    x-ref="passphrasePlainTextInput"
                                    x-model="plainText"
                                    x-on:input="persistPlainTextDraft()"
                                    x-on:keydown="if ($event.key === 'Enter' && ! $event.shiftKey && ! $event.isComposing && !working) { $event.preventDefault(); sendEncryptedMessage(); }"
                                    class="min-h-24 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                    placeholder="{{ __('chat.conversation.encrypted_message_placeholder') }}"
                                ></textarea>

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        @if ($imagesEnabled)
                                            <label class="inline-flex h-9 cursor-pointer items-center justify-center rounded-xl border border-amber-300/90 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:border-brand-300 hover:bg-amber-50 dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100 dark:hover:border-brand-400/65 dark:hover:bg-zinc-900">
                                                <input type="file" wire:model="image" class="hidden" />
                                                {{ __('chat.conversation.attach_image') }}
                                            </label>
                                        @endif
                                    </div>

                                    <x-button type="button" variant="primary" x-bind:disabled="working" x-on:click="sendEncryptedMessage()">
                                        <span x-show="!working">{{ __('chat.common.send') }}</span>
                                        <span x-show="working">{{ __('chat.conversation.encrypting') }}</span>
                                    </x-button>
                                </div>
                            </div>
                        </template>

                        <p x-show="error.length > 0" x-text="error" class="text-xs font-medium text-rose-600 dark:text-rose-300"></p>
                    </div>
                @else
                    <div
                        class="rounded-xl border border-amber-200/80 bg-gradient-to-b from-white to-amber-50/70 p-2.5 shadow-soft dark:border-brand-500/30 dark:from-zinc-950 dark:to-zinc-900/90"
                        x-data="{
                            draftCacheKey: @js('schat-draft-'.$context.'-'.((int) $conversationId)),
                            emojiOpen: false,
                            sending: false,
                            showBurst: false,
                            floatingEmoji: '\u2728',
                            emojis: [
                                '\uD83D\uDE00', '\uD83D\uDE01', '\uD83D\uDE02', '\uD83D\uDE0D',
                                '\uD83E\uDD29', '\uD83D\uDE0E', '\uD83E\uDD73', '\uD83E\uDD1D',
                                '\uD83D\uDE4F', '\uD83D\uDD25', '\uD83D\uDCA1', '\u2705',
                                '\uD83C\uDF89', '\uD83D\uDCCE', '\uD83D\uDCAC', '\uD83D\uDE80'
                            ],
                            initDraft() {
                                const input = this.$refs.draftInput;
                                if (!input) {
                                    return;
                                }

                                const cachedDraft = sessionStorage.getItem(this.draftCacheKey);
                                if (!cachedDraft) {
                                    return;
                                }

                                input.value = cachedDraft;
                                input.dispatchEvent(new Event('input', { bubbles: true }));

                                if (document.activeElement === input && typeof input.setSelectionRange === 'function') {
                                    input.setSelectionRange(cachedDraft.length, cachedDraft.length);
                                }
                            },
                            persistDraft() {
                                const input = this.$refs.draftInput;
                                if (!input) {
                                    return;
                                }

                                const value = input.value ?? '';
                                if (value.trim() === '') {
                                    sessionStorage.removeItem(this.draftCacheKey);
                                    return;
                                }

                                sessionStorage.setItem(this.draftCacheKey, value);
                            },
                            clearDraftCache() {
                                sessionStorage.removeItem(this.draftCacheKey);
                            },
                            clearDraftInput() {
                                const input = this.$refs.draftInput;
                                if (!input) {
                                    return;
                                }

                                input.value = '';
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                            },
                            keepDraftInputFocus() {
                                const input = this.$refs.draftInput;
                                if (!input) {
                                    return;
                                }

                                requestAnimationFrame(() => {
                                    input.focus({ preventScroll: true });
                                    if (typeof input.setSelectionRange === 'function') {
                                        const end = (input.value ?? '').length;
                                        input.setSelectionRange(end, end);
                                    }
                                });
                            },
                            toggleEmoji() {
                                this.emojiOpen = !this.emojiOpen;
                            },
                            closeEmoji() {
                                this.emojiOpen = false;
                            },
                            insertEmoji(emoji) {
                                const input = this.$refs.draftInput;
                                if (!input) {
                                    return;
                                }

                                const value = input.value ?? '';
                                const selectionStart = Number.isInteger(input.selectionStart) ? input.selectionStart : value.length;
                                const selectionEnd = Number.isInteger(input.selectionEnd) ? input.selectionEnd : value.length;
                                const nextValue = `${value.slice(0, selectionStart)}${emoji}${value.slice(selectionEnd)}`;
                                const nextCaret = selectionStart + emoji.length;

                                input.value = nextValue;
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                input.focus();
                                input.setSelectionRange(nextCaret, nextCaret);

                                this.floatingEmoji = emoji;
                                this.showBurst = true;
                                setTimeout(() => {
                                    this.showBurst = false;
                                }, 480);
                            },
                            async requestSendText() {
                                if (this.sending) {
                                    return;
                                }

                                const input = this.$refs.draftInput;
                                const outgoing = input?.value ?? '';
                                if (outgoing.trim() === '') {
                                    return;
                                }

                                this.sending = true;
                                try {
                                    await this.$wire.sendText();
                                    this.clearDraftCache();
                                    this.clearDraftInput();
                                    this.keepDraftInputFocus();
                                } finally {
                                    this.sending = false;
                                }
                            },
                            async sendTextFromKeyboard(event) {
                                if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
                                    return;
                                }

                                event.preventDefault();
                                this.closeEmoji();
                                await this.requestSendText();
                            },
                        }"
                        x-init="initDraft()"
                        x-on:keydown.escape.window="closeEmoji()"
                    >
                        <textarea
                            wire:model.defer="draftText"
                            data-chat-composer-input
                            x-ref="draftInput"
                            x-on:input="persistDraft()"
                            x-on:focus="closeEmoji()"
                            x-on:keydown="sendTextFromKeyboard($event)"
                            class="min-h-12 max-h-28 w-full resize-y border-0 bg-transparent px-1 py-1 text-sm leading-6 text-slate-800 placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-0 dark:text-brand-50 dark:placeholder:text-brand-200/40"
                            placeholder="{{ __('chat.conversation.message_placeholder') }}"
                        ></textarea>

                        <div class="mt-1.5 flex items-center justify-between gap-2 border-t border-amber-200/90 pt-1.5 dark:border-brand-500/22">
                            <div class="relative flex items-center gap-2">
                                <span
                                    x-cloak
                                    x-show="showBurst"
                                    x-text="floatingEmoji"
                                    x-transition:enter="transition ease-out duration-350"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-75"
                                    x-transition:enter-end="opacity-100 -translate-y-4 scale-110"
                                    x-transition:leave="transition ease-in duration-220"
                                    x-transition:leave-start="opacity-100 -translate-y-4 scale-110"
                                    x-transition:leave-end="opacity-0 -translate-y-8 scale-75"
                                    class="pointer-events-none absolute -top-1 left-3 z-30 text-lg"
                                ></span>

                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-300/90 bg-white text-sm text-slate-700 transition hover:-translate-y-0.5 hover:border-brand-300 hover:bg-amber-50 dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100 dark:hover:border-brand-400/65 dark:hover:bg-zinc-900"
                                    x-on:click="toggleEmoji()"
                                    :aria-expanded="emojiOpen ? 'true' : 'false'"
                                    aria-haspopup="dialog"
                                    aria-label="{{ __('chat.conversation.emoji_button_label') }}"
                                >
                                    <span class="transition-transform duration-200" :class="emojiOpen ? 'scale-110' : ''">&#x1F60A;</span>
                                </button>

                                <div
                                    x-cloak
                                    x-show="emojiOpen"
                                    x-transition:enter="transition ease-out duration-180"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-120"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    x-on:click.outside="closeEmoji()"
                                    class="absolute bottom-12 {{ app()->isLocale('fa') ? 'right-0' : 'left-0' }} z-20 w-64 rounded-2xl border border-amber-200/80 bg-white/96 p-3 shadow-[var(--shadow-floating)] backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/96"
                                >
                                    <div class="mb-2 flex items-center justify-between">
                                        <p class="text-xs font-semibold text-slate-700 dark:text-brand-100">{{ __('chat.conversation.emoji_picker_title') }}</p>
                                        <button type="button" class="rounded-md p-1 text-slate-400 transition hover:bg-amber-100 hover:text-slate-700 dark:text-brand-200/60 dark:hover:bg-brand-500/12 dark:hover:text-brand-100" x-on:click="closeEmoji()">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-8 gap-1.5">
                                        <template x-for="emoji in emojis" :key="emoji">
                                            <button
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-base transition hover:bg-amber-100 dark:hover:bg-brand-500/12"
                                                x-on:click="insertEmoji(emoji)"
                                            >
                                                <span x-text="emoji"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                @if ($imagesEnabled)
                                    <label class="inline-flex h-9 cursor-pointer items-center justify-center rounded-xl border border-amber-300/90 bg-white px-3 text-xs font-medium text-slate-700 transition hover:-translate-y-0.5 hover:border-brand-300 hover:bg-amber-50 dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100 dark:hover:border-brand-400/65 dark:hover:bg-zinc-900">
                                        <input type="file" wire:model="image" class="hidden" />
                                        {{ __('chat.conversation.attach_image') }}
                                    </label>
                                @endif
                            </div>

                            <x-button type="button" variant="primary" x-bind:disabled="sending" x-on:click="requestSendText()">
                                <span x-show="!sending">{{ __('chat.common.send') }}</span>
                                <span x-show="sending">{{ __('chat.conversation.sending') }}</span>
                            </x-button>
                        </div>
                    </div>
                @endif

                @if ($image)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-amber-300/90 bg-amber-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100">
                            {{ method_exists($image, 'getClientOriginalName') ? $image->getClientOriginalName() : __('chat.conversation.image_selected') }}
                        </span>
                        <x-button type="button" size="sm" variant="secondary" wire:click="sendImage">
                            <span wire:loading.remove wire:target="sendImage">{{ __('chat.conversation.upload') }}</span>
                            <span wire:loading wire:target="sendImage">{{ __('chat.conversation.uploading') }}</span>
                        </x-button>
                        <x-button type="button" size="sm" variant="ghost" wire:click="clearImage">{{ __('chat.common.remove') }}</x-button>
                    </div>
                @endif

                <p class="text-xs text-slate-500 dark:text-brand-200/60">
                    {{ __('chat.conversation.allowed_formats', ['max_kb' => (int) config('chat.max_image_kb', 2048)]) }}
                </p>

                @error('text')
                    <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
                @error('image')
                    <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif
</div>

@once
    <script>
        (() => {
            if (window.SChatPassphrase) {
                return;
            }

            const encoder = new TextEncoder();
            const decoder = new TextDecoder();
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            const conversationsBaseUrl = @js(url('/chat/conversations'));
            const i18n = {
                requestFailed: @js(__('chat.conversation.js.request_failed')),
                passphraseRequired: @js(__('chat.conversation.js.passphrase_required')),
                passphraseConfirmMismatch: @js(__('chat.conversation.js.passphrase_confirm_mismatch')),
                passphraseMetaMissing: @js(__('chat.conversation.js.passphrase_meta_missing')),
                challengeFailed: @js(__('chat.conversation.js.challenge_failed')),
                verifyFailed: @js(__('chat.conversation.js.verify_failed')),
                messageEmpty: @js(__('chat.conversation.js.message_empty')),
                unlockRequired: @js(__('chat.conversation.js.unlock_required')),
                sendFailed: @js(__('chat.conversation.js.send_failed')),
                lockedMessage: @js(__('chat.conversation.js.locked_message')),
                decryptFailed: @js(__('chat.conversation.js.decrypt_failed')),
            };

            const cacheKey = (conversationId) => `schat-passphrase-${conversationId}`;
            const passphraseDraftKey = (conversationId) => `schat-passphrase-draft-${conversationId}`;
            const decryptedMessageKey = (conversationId, messageId) => `schat-passphrase-dec-${conversationId}-${messageId}`;
            const getCachedPassphrase = (conversationId) => {
                const key = cacheKey(conversationId);

                try {
                    const sessionValue = sessionStorage.getItem(key);
                    if (sessionValue) {
                        return sessionValue;
                    }
                } catch (error) {
                    //
                }

                try {
                    const localValue = localStorage.getItem(key);
                    if (localValue) {
                        return localValue;
                    }
                } catch (error) {
                    //
                }

                return '';
            };
            const setCachedPassphrase = (conversationId, passphrase) => {
                const key = cacheKey(conversationId);

                try {
                    sessionStorage.setItem(key, passphrase);
                } catch (error) {
                    //
                }

                try {
                    localStorage.setItem(key, passphrase);
                } catch (error) {
                    //
                }
            };
            const clearCachedPassphrase = (conversationId) => {
                const key = cacheKey(conversationId);

                try {
                    sessionStorage.removeItem(key);
                } catch (error) {
                    //
                }

                try {
                    localStorage.removeItem(key);
                } catch (error) {
                    //
                }
            };
            const getCachedDecryptedMessage = (conversationId, messageId) => {
                if (!messageId) {
                    return null;
                }

                try {
                    const value = sessionStorage.getItem(decryptedMessageKey(conversationId, messageId));
                    return value === null ? null : value;
                } catch (error) {
                    return null;
                }
            };
            const setCachedDecryptedMessage = (conversationId, messageId, text) => {
                if (!messageId) {
                    return;
                }

                try {
                    sessionStorage.setItem(decryptedMessageKey(conversationId, messageId), text);
                } catch (error) {
                    //
                }
            };
            const clearDecryptedConversationCache = (conversationId) => {
                const prefix = `schat-passphrase-dec-${conversationId}-`;

                try {
                    for (let index = sessionStorage.length - 1; index >= 0; index -= 1) {
                        const storageKey = sessionStorage.key(index);

                        if (storageKey && storageKey.startsWith(prefix)) {
                            sessionStorage.removeItem(storageKey);
                        }
                    }
                } catch (error) {
                    //
                }
            };

            const base64ToBytes = (base64) => Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
            const bytesToBase64 = (bytes) => btoa(String.fromCharCode(...bytes));

            const deriveKey = async (passphrase, saltBytes, iterations) => {
                const keyMaterial = await crypto.subtle.importKey(
                    'raw',
                    encoder.encode(passphrase),
                    { name: 'PBKDF2' },
                    false,
                    ['deriveKey']
                );

                return crypto.subtle.deriveKey(
                    {
                        name: 'PBKDF2',
                        salt: saltBytes,
                        iterations,
                        hash: 'SHA-256',
                    },
                    keyMaterial,
                    { name: 'AES-GCM', length: 256 },
                    false,
                    ['encrypt', 'decrypt']
                );
            };

            const encryptMessage = async ({ passphrase, text, iter, algo, kdf }) => {
                const ivBytes = crypto.getRandomValues(new Uint8Array(12));
                const saltBytes = crypto.getRandomValues(new Uint8Array(16));
                const key = await deriveKey(passphrase, saltBytes, iter);
                const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: ivBytes }, key, encoder.encode(text));

                return {
                    ciphertextBase64: bytesToBase64(new Uint8Array(encrypted)),
                    cryptoMeta: {
                        mode: 'passphrase',
                        algo,
                        v: 1,
                        iv: bytesToBase64(ivBytes),
                        salt: bytesToBase64(saltBytes),
                        kdf,
                        iter,
                    },
                };
            };

            const decryptMessage = async (passphrase, ciphertextBase64, cryptoMeta) => {
                const ivBytes = base64ToBytes(cryptoMeta.iv);
                const saltBytes = base64ToBytes(cryptoMeta.salt);
                const key = await deriveKey(passphrase, saltBytes, Number(cryptoMeta.iter));
                const decrypted = await crypto.subtle.decrypt(
                    { name: 'AES-GCM', iv: ivBytes },
                    key,
                    base64ToBytes(ciphertextBase64)
                );

                return decoder.decode(decrypted);
            };

            const encodeVerifyBlob = (payload) => bytesToBase64(encoder.encode(JSON.stringify(payload)));
            const decodeVerifyBlob = (verifyBlob) => JSON.parse(decoder.decode(base64ToBytes(verifyBlob)));

            const createVerifyBlob = async (passphrase, challenge) => {
                const saltBytes = base64ToBytes(challenge.salt);
                const tokenBytes = base64ToBytes(challenge.verify_token);
                const ivBytes = crypto.getRandomValues(new Uint8Array(12));
                const key = await deriveKey(passphrase, saltBytes, Number(challenge.iter));
                const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: ivBytes }, key, tokenBytes);

                return encodeVerifyBlob({
                    iv: bytesToBase64(ivBytes),
                    ciphertext: bytesToBase64(new Uint8Array(encrypted)),
                });
            };

            const verifyPassphrase = async (passphrase, meta) => {
                const verifyPayload = decodeVerifyBlob(meta.verify_blob);
                const ivBytes = base64ToBytes(verifyPayload.iv);
                const ciphertextBytes = base64ToBytes(verifyPayload.ciphertext);
                const saltBytes = base64ToBytes(meta.salt);
                const key = await deriveKey(passphrase, saltBytes, Number(meta.iter));

                await crypto.subtle.decrypt({ name: 'AES-GCM', iv: ivBytes }, key, ciphertextBytes);
            };

            window.SChatPassphrase = {
                composer: ({ conversationId, context, configured, iter, algo, kdf }) => ({
                    conversationId,
                    context,
                    configured,
                    iter,
                    algo,
                    kdf,
                    passphrase: '',
                    confirmPassphrase: '',
                    revealPassphrase: false,
                    revealConfirmPassphrase: false,
                    plainText: '',
                    unlocked: Boolean(getCachedPassphrase(conversationId)),
                    working: false,
                    error: '',
                    init() {
                        const cachedPassphrase = getCachedPassphrase(this.conversationId);
                        const cachedDraft = sessionStorage.getItem(passphraseDraftKey(this.conversationId));

                        if (cachedDraft) {
                            this.plainText = cachedDraft;
                        }

                        if (cachedPassphrase) {
                            this.passphrase = cachedPassphrase;
                            this.unlocked = true;
                            window.dispatchEvent(new CustomEvent('schat-passphrase-updated', { detail: { conversationId: this.conversationId } }));
                        }
                    },
                    persistPlainTextDraft() {
                        const text = this.plainText ?? '';

                        if (text.trim() === '') {
                            sessionStorage.removeItem(passphraseDraftKey(this.conversationId));
                            return;
                        }

                        sessionStorage.setItem(passphraseDraftKey(this.conversationId), text);
                    },
                    focusPlainTextComposer() {
                        const input = this.$refs.passphrasePlainTextInput;
                        if (!input) {
                            return;
                        }

                        requestAnimationFrame(() => {
                            input.focus({ preventScroll: true });
                            if (typeof input.setSelectionRange === 'function') {
                                const end = (input.value ?? '').length;
                                input.setSelectionRange(end, end);
                            }
                        });
                    },
                    conversationUrl(path) {
                        return `${conversationsBaseUrl}/${this.conversationId}/${path}`;
                    },
                    async requestJson(path, method, payload = null) {
                        const headers = {
                            Accept: 'application/json',
                        };

                        if (csrfToken) {
                            headers['X-CSRF-TOKEN'] = csrfToken;
                        }

                        let body;
                        if (payload !== null) {
                            headers['Content-Type'] = 'application/json';
                            body = JSON.stringify(payload);
                        }

                        const response = await fetch(this.conversationUrl(path), {
                            method,
                            headers,
                            credentials: 'same-origin',
                            body,
                        });

                        const responseData = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const validationMessage = responseData?.message
                                || (responseData?.errors ? Object.values(responseData.errors)[0]?.[0] : null);

                            throw new Error(validationMessage || `${i18n.requestFailed} ${response.status}.`);
                        }

                        return responseData;
                    },
                    forceRefresh() {
                        if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                            window.Livewire.dispatch(`conversation-force-refresh.${this.context}`, {
                                conversationId: this.conversationId,
                            });
                        }
                    },
                    async unlock(silent = false) {
                        this.error = '';
                        this.working = true;

                        try {
                            if (!this.passphrase || this.passphrase.length < 3) {
                                throw new Error(i18n.passphraseRequired);
                            }

                            if (!this.configured && this.passphrase !== this.confirmPassphrase) {
                                throw new Error(i18n.passphraseConfirmMismatch);
                            }

                            if (this.configured) {
                                const meta = await this.requestJson('passphrase/meta', 'GET');
                                if (!meta) {
                                    throw new Error(i18n.passphraseMetaMissing);
                                }

                                await verifyPassphrase(this.passphrase, meta);
                            } else {
                                const challenge = await this.requestJson('passphrase/challenge', 'POST');
                                if (!challenge) {
                                    throw new Error(i18n.challengeFailed);
                                }

                                const verifyBlob = await createVerifyBlob(this.passphrase, challenge);
                                await this.requestJson('passphrase/verify-blob', 'PUT', {
                                    verify_blob_base64: verifyBlob,
                                    iter: this.iter,
                                });
                                this.configured = true;
                            }

                            this.unlocked = true;
                            setCachedPassphrase(this.conversationId, this.passphrase);
                            window.dispatchEvent(new CustomEvent('schat-passphrase-updated', { detail: { conversationId: this.conversationId } }));
                        } catch (error) {
                            this.unlocked = false;
                            if (!silent) {
                                this.error = error instanceof Error ? error.message : i18n.verifyFailed;
                            }
                        } finally {
                            this.working = false;
                        }
                    },
                    lock() {
                        this.unlocked = false;
                        this.plainText = '';
                        clearCachedPassphrase(this.conversationId);
                        clearDecryptedConversationCache(this.conversationId);
                        sessionStorage.removeItem(passphraseDraftKey(this.conversationId));
                        window.dispatchEvent(new CustomEvent('schat-passphrase-updated', { detail: { conversationId: this.conversationId } }));
                    },
                    async sendEncryptedMessage() {
                        this.error = '';

                        const text = this.plainText.trim();
                        if (!text) {
                            this.error = i18n.messageEmpty;
                            return;
                        }

                        if (!this.unlocked) {
                            this.error = i18n.unlockRequired;
                            return;
                        }

                        this.working = true;
                        try {
                            const encrypted = await encryptMessage({
                                passphrase: this.passphrase,
                                text,
                                iter: this.iter,
                                algo: this.algo,
                                kdf: this.kdf,
                            });

                            await this.requestJson('messages/text', 'POST', {
                                ciphertext_base64: encrypted.ciphertextBase64,
                                crypto_meta: encrypted.cryptoMeta,
                            });

                            this.plainText = '';
                            this.persistPlainTextDraft();
                            this.focusPlainTextComposer();
                        } catch (error) {
                            this.error = error instanceof Error ? error.message : i18n.sendFailed;
                        } finally {
                            this.working = false;
                        }
                    },
                }),
                messageVM: ({ messageId, conversationId, ciphertextBase64, cryptoMeta }) => ({
                    messageId,
                    conversationId,
                    ciphertextBase64,
                    cryptoMeta,
                    text: '',
                    init() {
                        const cachedText = getCachedDecryptedMessage(this.conversationId, this.messageId);
                        if (cachedText !== null) {
                            this.text = cachedText;
                            return;
                        }

                        this.decrypt();
                    },
                    async decrypt() {
                        const passphrase = getCachedPassphrase(this.conversationId);
                        if (!passphrase) {
                            this.text = i18n.lockedMessage;
                            return;
                        }

                        try {
                            const decryptedText = await decryptMessage(passphrase, this.ciphertextBase64, this.cryptoMeta);
                            this.text = decryptedText;
                            setCachedDecryptedMessage(this.conversationId, this.messageId, decryptedText);
                        } catch (error) {
                            this.text = i18n.decryptFailed;
                        }
                    },
                }),
            };
        })();
    </script>
@endonce
