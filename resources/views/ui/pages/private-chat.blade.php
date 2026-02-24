@php
    $conversationId = request()->integer('conversation');
@endphp

<x-app-shell :title="__('chat.pages.private_title')" app-name="SChat" :chat-id="auth()->user()?->chat_id ?? 'private_guest'">
    <livewire:chat.chat-shell-livewire mode="private" :conversation="$conversationId" />

    <x-slot:sidebar>
        <livewire:chat.sidebar-online-users-livewire mode="private" :selected-conversation-id="$conversationId" :key="'chat-sidebar-desktop-private'" />
    </x-slot:sidebar>

    <x-slot:mobileSidebar>
        <livewire:chat.sidebar-online-users-livewire mode="private" :selected-conversation-id="$conversationId" :key="'chat-sidebar-mobile-private'" />
    </x-slot:mobileSidebar>
</x-app-shell>
