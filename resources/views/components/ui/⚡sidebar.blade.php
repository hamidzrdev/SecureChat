<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\PresenceService;
use Livewire\Component;

new class extends Component
{
    public string $context = 'public';

    public bool $chooserOpen = false;

    public ?int $selectedUserId = null;

    public bool $autoOpenIncomingChat = false;

    public array $onlineUsers = [];

    public array $conversations = [];

    public function mount(string $context = 'public'): void
    {
        $this->context = $context;
        $this->autoOpenIncomingChat = (bool) config('chat.auto_open_incoming_private_chat', false);
        $this->loadOnlineUsers();
        $this->loadConversations();
    }

    public function getListeners(): array
    {
        $authUserId = auth()->id();

        if (! $authUserId) {
            return [];
        }

        return [
            "echo-private:private-user.{$authUserId},.PrivateChatOpened" => 'handlePrivateChatOpened',
        ];
    }

    public function startPrivateChat(bool $isPassphrase, ConversationService $conversationService): void
    {
        if (! $this->selectedUserId) {
            $this->addError('chat', 'Please select a user first.');

            return;
        }

        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $targetUser = User::query()->find($this->selectedUserId);

        if (! $targetUser instanceof User) {
            $this->addError('chat', 'Selected user does not exist.');

            return;
        }

        $conversation = $conversationService->startPrivateChat(
            opener: $authenticatedUser,
            target: $targetUser,
            isPassphrase: $isPassphrase,
        );

        $this->upsertConversation([
            'conversation_id' => $conversation->getKey(),
            'peer_chat_id' => (string) $targetUser->chat_id,
            'peer_user_id' => $targetUser->getKey(),
            'is_passphrase' => $isPassphrase,
            'opened_at' => now()->toIso8601String(),
        ]);

        $this->chooserOpen = false;
        $this->selectedUserId = null;

        $this->redirectRoute(
            $isPassphrase ? 'chat.passphrase' : 'chat.private',
            [
                'conversation' => $conversation->getKey(),
                'user' => (string) $targetUser->chat_id,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function handlePrivateChatOpened(array $event): void
    {
        $this->upsertConversation([
            'conversation_id' => (int) ($event['conversation_id'] ?? 0),
            'peer_chat_id' => (string) ($event['opener_chat_id'] ?? 'unknown'),
            'peer_user_id' => (int) ($event['opener_user_id'] ?? 0),
            'is_passphrase' => (bool) ($event['is_passphrase'] ?? false),
            'opened_at' => (string) ($event['opened_at'] ?? now()->toIso8601String()),
        ]);

        if ($this->autoOpenIncomingChat) {
            $this->dispatch('chat-open-requested', [
                'conversation_id' => (int) ($event['conversation_id'] ?? 0),
                'is_passphrase' => (bool) ($event['is_passphrase'] ?? false),
            ]);
        }
    }

    private function loadOnlineUsers(): void
    {
        $authUser = auth()->user();

        if (! $authUser instanceof User || ! config('chat.online_list_enabled')) {
            $this->onlineUsers = [];

            return;
        }

        $presenceService = app(PresenceService::class);
        $onlineUsers = $presenceService->onlineUsersQuery()
            ->whereKeyNot($authUser->getKey())
            ->limit(25)
            ->get();

        $this->onlineUsers = $onlineUsers
            ->map(function (User $user): array {
                $status = $user->last_seen_at
                    ? 'active '.$user->last_seen_at->diffForHumans()
                    : 'active now';

                return [
                    'id' => (int) $user->getKey(),
                    'name' => (string) $user->chat_id,
                    'username' => (string) $user->chat_id,
                    'status' => $status,
                ];
            })
            ->values()
            ->all();
    }

    private function loadConversations(): void
    {
        $authUser = auth()->user();

        if (! $authUser instanceof User) {
            $this->conversations = [];

            return;
        }

        $conversations = Conversation::query()
            ->where('type', 'private')
            ->whereHas('users', static fn ($query) => $query->whereKey($authUser->getKey()))
            ->with(['users' => static fn ($query) => $query->select('users.id', 'chat_id')])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $this->conversations = $conversations
            ->map(function (Conversation $conversation) use ($authUser): array {
                $peer = $conversation->users
                    ->firstWhere('id', '!=', $authUser->getKey());

                return [
                    'conversation_id' => (int) $conversation->getKey(),
                    'peer_chat_id' => (string) ($peer?->chat_id ?? 'unknown'),
                    'peer_user_id' => (int) ($peer?->getKey() ?? 0),
                    'is_passphrase' => (bool) $conversation->is_passphrase,
                    'opened_at' => optional($conversation->updated_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{conversation_id:int, peer_chat_id:string, peer_user_id:int, is_passphrase:bool, opened_at:string}  $conversation
     */
    private function upsertConversation(array $conversation): void
    {
        if ($conversation['conversation_id'] <= 0) {
            return;
        }

        $filtered = collect($this->conversations)
            ->reject(static fn (array $item): bool => (int) ($item['conversation_id'] ?? 0) === $conversation['conversation_id'])
            ->values()
            ->all();

        array_unshift($filtered, $conversation);

        $this->conversations = array_slice($filtered, 0, 20);
    }
};
?>

<div
    x-data="{
        onlineUsers: @js($onlineUsers),
        selectedUserId: $wire.entangle('selectedUserId'),
        chooserOpen: $wire.entangle('chooserOpen'),
        selectedUser() {
            return this.onlineUsers.find((user) => Number(user.id) === Number(this.selectedUserId)) ?? null;
        },
    }"
    class="space-y-4"
>
    <x-card title="Online users" description="{{ count($onlineUsers) }} online right now">
        <div class="space-y-2">
            @foreach ($onlineUsers as $user)
                <button
                    type="button"
                    class="group flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:hover:bg-slate-800"
                    x-on:click="selectedUserId = {{ (int) $user['id'] }}; chooserOpen = true"
                >
                    <x-avatar :name="$user['name']" size="sm" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $user['name'] }}</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user['status'] }}</p>
                    </div>

                    <svg class="h-4 w-4 text-slate-300 transition group-hover:text-slate-500 dark:text-slate-600 dark:group-hover:text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M7 4l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            @endforeach
        </div>
    </x-card>

    <x-card title="Conversations" description="{{ count($conversations) }} recent">
        <div class="space-y-2">
            @forelse ($conversations as $conversation)
                <a
                    href="{{ $conversation['is_passphrase'] ? route('chat.passphrase', ['conversation' => $conversation['conversation_id'], 'user' => $conversation['peer_chat_id']]) : route('chat.private', ['conversation' => $conversation['conversation_id'], 'user' => $conversation['peer_chat_id']]) }}"
                    wire:key="conversation-{{ $conversation['conversation_id'] }}"
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/15"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ '@'.$conversation['peer_chat_id'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $conversation['is_passphrase'] ? 'Passphrase chat' : 'Normal chat' }}</p>
                    </div>

                    <x-badge :variant="$conversation['is_passphrase'] ? 'warning' : 'secondary'" size="sm">
                        #{{ $conversation['conversation_id'] }}
                    </x-badge>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No private chats yet.
                </p>
            @endforelse
        </div>
    </x-card>

    <div x-cloak x-show="chooserOpen" class="fixed inset-0 z-[75] flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" x-on:click="chooserOpen = false"></div>

        <div
            x-show="chooserOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-soft dark:border-slate-700 dark:bg-slate-900"
        >
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Start chat</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    with <span class="font-medium text-slate-700 dark:text-slate-200" x-text="selectedUser() ? selectedUser().name : 'selected user'"></span>
                </p>
            </div>

            <div class="space-y-2">
                <button
                    type="button"
                    wire:click="startPrivateChat(false)"
                    wire:loading.attr="disabled"
                    wire:target="startPrivateChat"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 px-3.5 py-3 text-left text-sm font-medium text-slate-800 transition hover:border-brand-300 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:border-slate-700 dark:text-slate-100 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/15"
                >
                    <span>Normal chat</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Standard private mode</span>
                </button>

                <button
                    type="button"
                    wire:click="startPrivateChat(true)"
                    wire:loading.attr="disabled"
                    wire:target="startPrivateChat"
                    class="flex w-full items-center justify-between rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-left text-sm font-medium text-amber-900 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:border-amber-500/50 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/20"
                >
                    <span>Passphrase chat</span>
                    <span class="text-xs text-amber-700/80 dark:text-amber-200/80">Encrypted with passphrase</span>
                </button>
            </div>

            <div class="mt-4 flex justify-end">
                <x-button type="button" variant="ghost" x-on:click="chooserOpen = false">Cancel</x-button>
            </div>
        </div>
    </div>
</div>
