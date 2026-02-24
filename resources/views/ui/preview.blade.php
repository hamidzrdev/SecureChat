<x-app-shell :title="__('chat.preview.page_title')" app-name="SChat" chat-id="preview_user">
    <div class="space-y-5">
        <x-card :title="__('chat.preview.phase_title')" :description="__('chat.preview.phase_description')">
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('ui.login') }}" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('chat.preview.login_title') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('chat.preview.login_description') }}</p>
                </a>

                <a href="{{ route('ui.chat.public') }}" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('chat.preview.public_title') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('chat.preview.public_description') }}</p>
                </a>

                <a href="{{ route('ui.chat.private') }}" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('chat.preview.private_title') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('chat.preview.private_description') }}</p>
                </a>

                <a href="{{ route('ui.chat.passphrase') }}" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('chat.preview.passphrase_title') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('chat.preview.passphrase_description') }}</p>
                </a>
            </div>
        </x-card>

        <x-card :title="__('chat.preview.inventory_title')" :description="__('chat.preview.inventory_description')">
            <div class="flex flex-wrap gap-2">
                <x-badge variant="secondary">{{ __('chat.preview.component_login') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_public_chat') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_private_chat') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_passphrase_gate') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_sidebar') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_message_list') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_composer') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_emoji_picker') }}</x-badge>
                <x-badge variant="secondary">{{ __('chat.preview.component_image_lightbox') }}</x-badge>
            </div>
        </x-card>
    </div>

    <x-slot:sidebar>
        <x-card :title="__('chat.preview.notes_title')" :description="__('chat.preview.notes_description')">
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <li>{{ __('chat.preview.note_mocked') }}</li>
                <li>{{ __('chat.preview.note_visual') }}</li>
                <li>{{ __('chat.preview.note_theme_storage') }}</li>
            </ul>
        </x-card>
    </x-slot:sidebar>
</x-app-shell>
