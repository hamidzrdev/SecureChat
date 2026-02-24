<?php

use Livewire\Component;

new class extends Component
{
    public array $rooms = [];

    public array $members = [];

    public array $files = [];

    public function mount(): void
    {
        $this->rooms = [
            [
                'name' => 'Product Support',
                'snippet' => 'Reviewing onboarding changes',
                'unread' => 5,
            ],
            [
                'name' => 'Design Critique',
                'snippet' => 'Need feedback on dark theme spacing',
                'unread' => 2,
            ],
            [
                'name' => 'General',
                'snippet' => 'Weekly standup notes are posted',
                'unread' => 0,
            ],
        ];

        $this->members = [
            ['name' => 'Ava Hart', 'role' => 'Product Lead', 'online' => true],
            ['name' => 'Noah Kim', 'role' => 'Frontend Engineer', 'online' => true],
            ['name' => 'Mina Rao', 'role' => 'Support', 'online' => false],
        ];

        $this->files = [
            ['name' => 'onboarding-v3.pdf', 'size' => '1.2 MB', 'updated' => '2h ago'],
            ['name' => 'release-checklist.xlsx', 'size' => '420 KB', 'updated' => 'Yesterday'],
            ['name' => 'chat-ui-mocks.fig', 'size' => '8.4 MB', 'updated' => '2 days ago'],
        ];
    }
};
?>

<div class="space-y-4">
    <x-card title="Conversations" description="Pinned and recent rooms.">
        <div class="space-y-2">
            @foreach ($rooms as $room)
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl border border-transparent px-2 py-2 transition hover:border-slate-200 hover:bg-slate-100 dark:hover:border-slate-700 dark:hover:bg-slate-800"
                >
                    <x-avatar :name="$room['name']" size="sm" />

                    <span class="min-w-0 flex-1 text-left">
                        <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $room['name'] }}</span>
                        <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $room['snippet'] }}</span>
                    </span>

                    @if ($room['unread'] > 0)
                        <x-badge variant="primary" size="sm">{{ $room['unread'] }}</x-badge>
                    @endif
                </button>
            @endforeach
        </div>
    </x-card>

    <x-card title="Members" description="Presence indicators in current room.">
        <div class="space-y-3">
            @foreach ($members as $member)
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <x-avatar :name="$member['name']" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $member['name'] }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $member['role'] }}</p>
                        </div>
                    </div>

                    <span @class([
                        'h-2.5 w-2.5 rounded-full',
                        'bg-emerald-500' => $member['online'],
                        'bg-slate-300 dark:bg-slate-600' => ! $member['online'],
                    ])></span>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="Shared Files" description="Latest uploads in this room.">
        <ul class="space-y-2">
            @foreach ($files as $file)
                <li class="rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-800">
                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $file['name'] }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $file['size'] }} - {{ $file['updated'] }}</p>
                </li>
            @endforeach
        </ul>
    </x-card>
</div>
