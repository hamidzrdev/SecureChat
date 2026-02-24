<div
    x-data="{
        userId: @js(auth()->id()),
        conversationId: $wire.$entangle('selectedConversationId'),
    }"
    x-init="window.SChatRealtime?.configure({ userId, conversationId })"
    x-on:chat-shell-reconfigure-realtime.window="window.SChatRealtime?.configure({ userId: $event.detail.user_id, conversationId: $event.detail.conversation_id })"
    x-effect="window.SChatRealtime?.configure({ userId, conversationId })"
    class="space-y-4"
>
    <div wire:poll.30s="pingPresence"></div>

    @php
        $ttlMinutes = (int) config('chat.ttl_minutes', 120);
    @endphp



    @php
        $mainConversationId = $mode === 'public' ? $publicConversationId : null;
    @endphp

    @if (! $mainConversationId)
        <x-card
            :title="$mode === 'public' ? __('chat.chat_shell.public_timeline_title') : __('chat.chat_shell.private_messaging_title')"
            :description="$mode === 'public' ? __('chat.chat_shell.public_timeline_description') : __('chat.chat_shell.private_messaging_description')"
        >
            <p class="text-sm text-slate-500 dark:text-brand-200/65">{{ __('chat.chat_shell.realtime_notice') }}</p>
        </x-card>
    @else
        <livewire:chat.conversation-view-livewire
            :mode="$mode === 'public' ? 'public' : $mode"
            context="main"
            :conversation-id="$mainConversationId"
            :wire:key="'conversation-main-'.$mode.'-'.$mainConversationId"
        />
    @endif

    @if ($privateConversationModalOpen && $modalConversationId)
        @teleport('body')
            <div class="fixed inset-0 z-[95]">
                <button
                    type="button"
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                    wire:click="closePrivateConversationModal"
                    aria-label="{{ __('chat.chat_shell.close_private_chat') }}"
                ></button>

                <div class="absolute inset-x-2 bottom-2 top-14 mx-auto max-w-6xl overflow-hidden rounded-3xl border border-amber-200/80 bg-white/92 p-2 shadow-[var(--shadow-floating)] backdrop-blur-md dark:border-brand-500/30 dark:bg-zinc-950/88 sm:inset-x-6 sm:top-12 sm:p-3 lg:inset-x-12">
                    <div class="mb-2 flex items-center justify-between rounded-2xl border border-amber-200/80 bg-white/90 px-4 py-3 dark:border-brand-500/28 dark:bg-zinc-900/90">
                        <div class="min-w-0 space-y-1">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-brand-100">
                                {{ __('chat.chat_shell.private_conversation', ['id' => $modalConversationId]) }}
                            </p>
                            <div class="flex items-center gap-2">
                                <x-badge :variant="$modalConversationPassphrase ? 'warning' : 'secondary'" size="sm">
                                    {{ $modalConversationPassphrase ? __('chat.common.passphrase') : __('chat.common.normal') }}
                                </x-badge>
                                <span class="text-xs text-slate-500 dark:text-brand-200/65">{{ __('chat.chat_shell.private_modal_hint') }}</span>
                            </div>
                        </div>

                        <x-button type="button" variant="secondary" size="sm" wire:click="closePrivateConversationModal">
                            {{ __('chat.common.close') }}
                        </x-button>
                    </div>

                    <div class="h-[calc(100%-3.2rem)] overflow-hidden">
                        <livewire:chat.conversation-view-livewire
                            :mode="$modalConversationPassphrase ? 'passphrase' : 'private'"
                            context="modal"
                            :conversation-id="$modalConversationId"
                            :wire:key="'conversation-modal-'.$modalConversationId"
                        />
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
