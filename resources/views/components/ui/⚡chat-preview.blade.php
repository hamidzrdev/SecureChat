<?php

use Livewire\Component;

new class extends Component
{
    public array $messages = [];

    public array $quickActions = [];

    public array $toasts = [];

    public function mount(): void
    {
        $this->quickActions = [
            'Roadmap',
            'Release notes',
            'Escalate issue',
            'Export transcript',
        ];

        $this->messages = [
            [
                'id' => 1,
                'author' => 'System Bot',
                'body' => 'Welcome to the Product Support room. Mock data is enabled for this UI preview.',
                'time' => '09:04 AM',
                'outgoing' => false,
            ],
            [
                'id' => 2,
                'author' => 'Ava Hart',
                'body' => 'Morning team. I need feedback on the new onboarding flow before launch.',
                'time' => '09:10 AM',
                'outgoing' => false,
            ],
            [
                'id' => 3,
                'author' => 'You',
                'body' => 'Looks good. I suggest moving the verification note one step earlier for clarity.',
                'time' => '09:14 AM',
                'outgoing' => true,
            ],
            [
                'id' => 4,
                'author' => 'Ava Hart',
                'body' => 'Great point. I will update the copy and share a final pass this afternoon.',
                'time' => '09:17 AM',
                'outgoing' => false,
            ],
        ];

        $this->toasts = [
            [
                'type' => 'success',
                'title' => 'Message delivered',
                'message' => 'Your last reply has been queued.',
            ],
            [
                'type' => 'info',
                'title' => 'Sync complete',
                'message' => '42 new messages pulled from mock feed.',
            ],
        ];
    }
};
?>

<div class="space-y-6">
    <x-card padding="none" class="overflow-hidden">
        <div class="border-b border-slate-200 p-4 sm:p-5 dark:border-slate-800">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-avatar name="Product Support" size="md" />
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Product Support Room</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">3 members online</p>
                    </div>
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    <x-badge variant="success">Encrypted</x-badge>
                    <x-button size="sm" variant="secondary" x-on:click="$dispatch('open-modal', 'new-room-modal')">
                        New room
                    </x-button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($quickActions as $action)
                    <x-button size="sm" variant="ghost">{{ $action }}</x-button>
                @endforeach
            </div>
        </div>

        <div class="h-[30rem] overflow-y-auto px-4 py-5 sm:px-5">
            <div class="space-y-4 pb-24 lg:pb-6">
                @foreach ($messages as $message)
                    <article @class(['flex items-end gap-3', 'justify-end' => $message['outgoing']])>
                        @if (! $message['outgoing'])
                            <x-avatar name="{{ $message['author'] }}" size="sm" />
                        @endif

                        <div @class([
                            'max-w-[85%] rounded-2xl border px-3.5 py-2.5 text-sm shadow-soft sm:max-w-[70%]',
                            'border-brand-500 bg-brand-600 text-white' => $message['outgoing'],
                            'border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100' => ! $message['outgoing'],
                        ])>
                            <p class="leading-relaxed">{{ $message['body'] }}</p>
                            <p
                                @class([
                                    'mt-2 text-[11px]',
                                    'text-brand-100' => $message['outgoing'],
                                    'text-slate-500 dark:text-slate-400' => ! $message['outgoing'],
                                ])
                            >
                                {{ $message['author'] }} - {{ $message['time'] }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 lg:static lg:border-t lg:bg-transparent lg:p-4 lg:backdrop-blur-none lg:dark:bg-transparent">
            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-soft dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-end gap-2">
                    <x-button size="sm" variant="ghost" type="button">+</x-button>

                    <label for="composer" class="sr-only">Write a message</label>
                    <textarea
                        id="composer"
                        rows="1"
                        class="min-h-10 flex-1 resize-none rounded-xl border border-transparent bg-slate-100 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-0 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                        placeholder="Write your message..."
                    ></textarea>

                    <x-button size="sm" variant="primary" type="button">Send</x-button>
                </div>
            </div>
        </div>
    </x-card>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-card title="Controls" description="Inputs with help/error states, switches, and tooltips.">
            <div class="space-y-4">
                <x-input
                    label="Display name"
                    name="display_name"
                    placeholder="Ava Hart"
                    help="This name is visible to room members."
                />

                <x-input
                    label="Workspace password"
                    name="workspace_password"
                    type="password"
                    placeholder="Type a secure password"
                    help="Use at least 12 characters."
                />

                <x-input
                    label="Public handle"
                    name="public_handle"
                    placeholder="@ava_hart"
                    error="This handle is already reserved."
                />

                <x-input
                    label="Upload attachment"
                    name="attachment"
                    type="file"
                    help="PNG, PDF, or ZIP up to 10MB."
                />

                <x-switch
                    name="desktop_notifications"
                    label="Desktop notifications"
                    description="Receive a notification for important mentions."
                    :checked="true"
                />

                <div x-data="{ tip: false }" class="relative inline-flex">
                    <x-button
                        variant="ghost"
                        size="sm"
                        type="button"
                        x-on:mouseenter="tip = true"
                        x-on:mouseleave="tip = false"
                    >
                        Hover for tooltip
                    </x-button>

                    <div
                        x-cloak
                        x-show="tip"
                        x-transition
                        class="absolute -top-10 left-0 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-soft dark:bg-slate-700"
                    >
                        Shortcut: Ctrl + K
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-end gap-2">
                    <x-button variant="ghost">Cancel</x-button>
                    <x-button variant="primary">Save changes</x-button>
                </div>
            </x-slot:footer>
        </x-card>

        <x-card title="Buttons & Feedback" description="Primary/secondary/ghost/destructive styles with statuses.">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    <x-button variant="primary">Primary</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="destructive">Destructive</x-button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-button variant="primary" size="sm">Small</x-button>
                    <x-button variant="primary">Default</x-button>
                    <x-button variant="primary" size="lg">Large</x-button>
                    <x-button variant="secondary" :loading="true">Loading</x-button>
                </div>

                <div class="h-px bg-slate-200 dark:bg-slate-800"></div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="primary">Primary</x-badge>
                    <x-badge variant="secondary">Secondary</x-badge>
                    <x-badge variant="success">Success</x-badge>
                    <x-badge variant="warning">Warning</x-badge>
                    <x-badge variant="danger">Danger</x-badge>
                </div>
            </div>
        </x-card>
    </div>

    <div class="pointer-events-none fixed bottom-24 right-4 z-40 flex w-[min(92vw,24rem)] flex-col gap-2 lg:bottom-4">
        @foreach ($toasts as $toast)
            <x-toast :type="$toast['type']" :title="$toast['title']" :message="$toast['message']" />
        @endforeach
    </div>

    <x-modal
        name="new-room-modal"
        title="Create New Room"
        description="This is a UI-only modal with mocked fields."
        max-width="md"
    >
        <div class="space-y-4">
            <x-input label="Room name" name="room_name" placeholder="e.g. Product QA" help="Short and descriptive room name." />
            <x-input label="Room topic" name="room_topic" placeholder="Release blockers and decisions" />

            <div class="flex justify-end gap-2">
                <x-button variant="ghost" x-on:click="$dispatch('close-modal', 'new-room-modal')">Cancel</x-button>
                <x-button variant="primary" x-on:click="$dispatch('close-modal', 'new-room-modal')">Create room</x-button>
            </div>
        </div>
    </x-modal>
</div>
