@php
    $conversationId = request()->integer('conversation');
@endphp

<x-app-shell :title="__('chat.pages.passphrase_title')" app-name="SChat" :chat-id="auth()->user()?->chat_id ?? 'secure_guest'">
    <livewire:chat.chat-shell-livewire mode="passphrase" :conversation="$conversationId" />

    <x-slot:sidebar>
        <livewire:chat.sidebar-online-users-livewire mode="private" :selected-conversation-id="$conversationId" />
    </x-slot:sidebar>
</x-app-shell>
