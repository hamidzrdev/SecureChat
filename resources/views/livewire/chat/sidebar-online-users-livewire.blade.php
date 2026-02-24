<div
    class="space-y-4"
    wire:poll.15s="refreshSidebarData"
    x-data="{
        chatModeModalOpen: false,
        targetUserId: null,
        targetChatId: '',
        openChatMode(userId, chatId) {
            this.targetUserId = Number(userId);
            this.targetChatId = chatId;
            this.chatModeModalOpen = true;

            if (window.SCHAT_DEBUG) {
                console.info('[SChat][Sidebar] openChatMode', { userId: this.targetUserId, chatId: this.targetChatId });
            }
        },
        startChat(isPassphrase) {
            const targetUserId = Number(this.targetUserId);
            if (!targetUserId) {
                if (window.SCHAT_DEBUG) {
                    console.warn('[SChat][Sidebar] startChat aborted: missing targetUserId');
                }

                return;
            }

            const isMobile = window.innerWidth < 1024;
            if (window.SCHAT_DEBUG) {
                console.info('[SChat][Sidebar] startChat begin', {
                    targetUserId,
                    isPassphrase,
                    isMobile,
                });
            }

            try {
                $wire.startPrivateChat(targetUserId, isPassphrase);
                if (window.SCHAT_DEBUG) {
                    console.info('[SChat][Sidebar] startPrivateChat call sent', { targetUserId, isPassphrase });
                }
            } catch (error) {
                if (window.SCHAT_DEBUG) {
                    console.error('[SChat][Sidebar] startPrivateChat call failed', error);
                }
            }

            this.chatModeModalOpen = false;
            if (isMobile) {
                window.setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('chat-mobile-close-sidebar'));
                }, 120);
            } else {
                window.dispatchEvent(new CustomEvent('chat-mobile-close-sidebar'));
            }
        },
    }"
    x-on:keydown.escape.window="chatModeModalOpen = false"
>
    <x-card
        :title="__('chat.sidebar.online_users_title')"
        :description="$onlineListEnabled ? __('chat.sidebar.active_count', ['count' => count($onlineUsers)]) : __('chat.sidebar.online_disabled_by_env')"
        class="border border-amber-200/80 bg-white/84 shadow-soft backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/82 dark:backdrop-blur-xl"
    >
        @if (! $onlineListEnabled)
            <p class="text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.sidebar.online_disabled_server') }}</p>
        @elseif (count($onlineUsers) === 0)
            <p class="text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.sidebar.no_online_users') }}</p>
        @else
            <div class="space-y-2">
                @foreach ($onlineUsers as $user)
                    <div wire:key="online-user-{{ $user['id'] }}" class="rounded-2xl border border-amber-200/80 bg-white/90 p-2.5 transition hover:border-brand-300 hover:bg-amber-50/70 dark:border-brand-500/22 dark:bg-zinc-900/75 dark:hover:border-brand-400/65 dark:hover:bg-brand-500/10">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800 dark:text-brand-100">{{ '@'.$user['chat_id'] }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-brand-200/55">{{ $user['status'] }}</p>
                            </div>

                            <div class="flex items-center gap-1">
                                <x-button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    data-user-id="{{ $user['id'] }}"
                                    data-chat-id="{{ '@'.$user['chat_id'] }}"
                                    x-on:click="openChatMode($el.dataset.userId, $el.dataset.chatId)"
                                >
                                    {{ __('chat.sidebar.start_chat') }}
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <x-card
        :title="__('chat.sidebar.private_conversations_title')"
        :description="__('chat.sidebar.items_count', ['count' => count($conversations)])"
        class="border border-amber-200/80 bg-white/84 shadow-soft backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/82 dark:backdrop-blur-xl"
    >
        @if (count($conversations) === 0)
            <p class="text-sm text-slate-500 dark:text-brand-200/60">{{ __('chat.sidebar.no_private_conversations') }}</p>
        @else
            <div class="space-y-2">
                @foreach ($conversations as $conversation)
                    <button
                        type="button"
                        wire:key="conversation-item-{{ $conversation['conversation_id'] }}"
                        wire:click="openConversation({{ $conversation['conversation_id'] }})"
                        @class([
                            'w-full rounded-2xl border px-3 py-2 text-left transition shadow-soft',
                            'border-brand-300/80 bg-brand-50/90 dark:border-brand-400/70 dark:bg-brand-500/16' => $selectedConversationId === $conversation['conversation_id'],
                            'border-amber-200/80 bg-white/90 hover:border-brand-300 hover:bg-amber-50/70 dark:border-brand-500/22 dark:bg-zinc-900/75 dark:hover:border-brand-400/55 dark:hover:bg-zinc-900/90' => $selectedConversationId !== $conversation['conversation_id'],
                        ])
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800 dark:text-brand-100">{{ '@'.$conversation['peer_chat_id'] }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-brand-200/55">#{{ $conversation['conversation_id'] }}</p>
                            </div>
                            <x-badge :variant="$conversation['is_passphrase'] ? 'warning' : 'secondary'" size="sm">
                                {{ $conversation['is_passphrase'] ? __('chat.common.passphrase') : __('chat.common.normal') }}
                            </x-badge>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </x-card>

    <div
        x-cloak
        x-show="chatModeModalOpen"
        class="fixed inset-0 z-[90] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/72 backdrop-blur-sm" x-on:click="chatModeModalOpen = false"></div>

        <div
            x-show="chatModeModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-md rounded-3xl border border-amber-200/80 bg-white/96 p-5 shadow-[var(--shadow-floating)] backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/96"
        >
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900 dark:text-brand-100">{{ __('chat.sidebar.choose_chat_mode') }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-brand-200/60">
                    {{ __('chat.sidebar.start_private_with') }} <span class="font-medium text-slate-700 dark:text-brand-100" x-text="targetChatId"></span>.
                </p>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <x-button
                    type="button"
                    variant="secondary"
                    x-bind:disabled="!targetUserId"
                    x-on:click.stop="startChat(false)"
                >
                    {{ __('chat.sidebar.normal_chat') }}
                </x-button>

                <x-button
                    type="button"
                    variant="warning"
                    x-bind:disabled="!targetUserId"
                    x-on:click.stop="startChat(true)"
                >
                    {{ __('chat.sidebar.passphrase_chat') }}
                </x-button>
            </div>

            <div class="mt-3 text-right">
                <x-button type="button" size="sm" variant="ghost" x-on:click="chatModeModalOpen = false">
                    {{ __('chat.common.cancel') }}
                </x-button>
            </div>
        </div>
    </div>
</div>
