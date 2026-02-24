<x-app-shell :title="__('chat.pages.login_title')" app-name="SChat" chat-id="guest_user">
    <div class="flex min-h-[calc(100vh-13rem)] items-center justify-center">
        <livewire:chat.login-livewire />
    </div>

    <x-slot:sidebar>
        <x-card :title="__('chat.login.security_tips_title')" :description="__('chat.login.security_tips_description')">
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <li>{{ __('chat.login.tip_unique_id') }}</li>
                <li>{{ __('chat.login.tip_set_password') }}</li>
                <li>{{ __('chat.login.tip_passphrase_available') }}</li>
            </ul>
        </x-card>
    </x-slot:sidebar>
</x-app-shell>
