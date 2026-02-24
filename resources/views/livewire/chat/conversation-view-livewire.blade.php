<div
    class="flex h-full min-h-0 flex-col gap-2"
    wire:poll.15s="pollMessages"
    x-data="{
        init() {
            this.$nextTick(() => this.scrollToBottom());

            const timeline = this.$refs.timeline;
            if (!timeline) {
                return;
            }

            this._observer = new MutationObserver(() => this.scrollToBottom());
            this._observer.observe(timeline, { childList: true, subtree: true });
        },
        scrollToBottom() {
            const timeline = this.$refs.timeline;
            if (!timeline) {
                return;
            }

            requestAnimationFrame(() => {
                timeline.scrollTop = timeline.scrollHeight;
            });
        },
    }"
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
                                <input
                                    type="password"
                                    x-model="passphrase"
                                    class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                    placeholder="{{ __('chat.conversation.passphrase_placeholder') }}"
                                />

                                <div x-show="!configured">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-brand-100">{{ __('chat.conversation.confirm_passphrase') }}</label>
                                    <input
                                        type="password"
                                        x-model="confirmPassphrase"
                                        class="mt-1 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                        placeholder="{{ __('chat.conversation.confirm_passphrase_placeholder') }}"
                                    />
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
                                    x-model="plainText"
                                    x-on:keydown="if ($event.key === 'Enter' && ! $event.shiftKey && ! $event.isComposing && !working) { $event.preventDefault(); sendEncryptedMessage(); }"
                                    class="min-h-24 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-950 dark:text-brand-50"
                                    placeholder="{{ __('chat.conversation.encrypted_message_placeholder') }}"
                                ></textarea>

                                <x-button type="button" variant="primary" x-bind:disabled="working" x-on:click="sendEncryptedMessage()">
                                    <span x-show="!working">{{ __('chat.common.send') }}</span>
                                    <span x-show="working">{{ __('chat.conversation.encrypting') }}</span>
                                </x-button>
                            </div>
                        </template>

                        <p x-show="error.length > 0" x-text="error" class="text-xs font-medium text-rose-600 dark:text-rose-300"></p>
                    </div>
                @else
                    <div
                        class="rounded-xl border border-amber-200/80 bg-gradient-to-b from-white to-amber-50/70 p-2.5 shadow-soft dark:border-brand-500/30 dark:from-zinc-950 dark:to-zinc-900/90"
                        x-data="{
                            emojiOpen: false,
                            showBurst: false,
                            floatingEmoji: '\u2728',
                            emojis: [
                                '\uD83D\uDE00', '\uD83D\uDE01', '\uD83D\uDE02', '\uD83D\uDE0D',
                                '\uD83E\uDD29', '\uD83D\uDE0E', '\uD83E\uDD73', '\uD83E\uDD1D',
                                '\uD83D\uDE4F', '\uD83D\uDD25', '\uD83D\uDCA1', '\u2705',
                                '\uD83C\uDF89', '\uD83D\uDCCE', '\uD83D\uDCAC', '\uD83D\uDE80'
                            ],
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
                            sendTextFromKeyboard(event) {
                                if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
                                    return;
                                }

                                event.preventDefault();
                                this.closeEmoji();
                                this.$wire.sendText();
                            },
                        }"
                        x-on:keydown.escape.window="closeEmoji()"
                    >
                        <textarea
                            wire:model.defer="draftText"
                            x-ref="draftInput"
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

                            <x-button type="button" variant="primary" wire:click="sendText">
                                <span wire:loading.remove wire:target="sendText">{{ __('chat.common.send') }}</span>
                                <span wire:loading wire:target="sendText">{{ __('chat.conversation.sending') }}</span>
                            </x-button>
                        </div>
                    </div>
                @endif

                @if ($imagesEnabled && $passphraseMode)
                    <label class="inline-flex h-9 w-fit cursor-pointer items-center justify-center rounded-xl border border-amber-300/90 bg-white px-3 text-xs font-medium text-slate-700 transition hover:border-brand-300 hover:bg-amber-50 dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100 dark:hover:border-brand-400/65 dark:hover:bg-zinc-900">
                        <input type="file" wire:model="image" class="hidden" />
                        {{ __('chat.conversation.attach_image') }}
                    </label>
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
                    plainText: '',
                    unlocked: false,
                    working: false,
                    error: '',
                    init() {
                        const cachedPassphrase = sessionStorage.getItem(cacheKey(this.conversationId));
                        if (cachedPassphrase) {
                            this.passphrase = cachedPassphrase;
                            this.unlock(true);
                        }
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
                            sessionStorage.setItem(cacheKey(this.conversationId), this.passphrase);
                            window.dispatchEvent(new CustomEvent('schat-passphrase-updated', { detail: { conversationId: this.conversationId } }));
                            this.forceRefresh();
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
                        sessionStorage.removeItem(cacheKey(this.conversationId));
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
                            this.forceRefresh();
                        } catch (error) {
                            this.error = error instanceof Error ? error.message : i18n.sendFailed;
                        } finally {
                            this.working = false;
                        }
                    },
                }),
                messageVM: ({ conversationId, ciphertextBase64, cryptoMeta }) => ({
                    conversationId,
                    ciphertextBase64,
                    cryptoMeta,
                    text: i18n.lockedMessage,
                    init() {
                        this.decrypt();
                    },
                    async decrypt() {
                        const passphrase = sessionStorage.getItem(cacheKey(this.conversationId));
                        if (!passphrase) {
                            this.text = i18n.lockedMessage;
                            return;
                        }

                        try {
                            this.text = await decryptMessage(passphrase, this.ciphertextBase64, this.cryptoMeta);
                        } catch (error) {
                            this.text = i18n.decryptFailed;
                        }
                    },
                }),
            };
        })();
    </script>
@endonce
