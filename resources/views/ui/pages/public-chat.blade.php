<x-app-shell :title="__('chat.pages.public_title')" app-name="SChat" :chat-id="auth()->user()?->chat_id ?? 'public_guest'">
    <livewire:chat.chat-shell-livewire mode="public" :conversation="request()->integer('conversation')" />

    <x-slot:sidebar>
        <livewire:chat.sidebar-online-users-livewire mode="public" :selected-conversation-id="request()->integer('conversation')" :key="'chat-sidebar-desktop-public'" />
    </x-slot:sidebar>

    <x-slot:mobileSidebar>
        <livewire:chat.sidebar-online-users-livewire mode="public" :selected-conversation-id="request()->integer('conversation')" :key="'chat-sidebar-mobile-public'" />
    </x-slot:mobileSidebar>
</x-app-shell>
