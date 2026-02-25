<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\PresenceService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SidebarOnlineUsersLivewire extends Component
{
    public string $mode = 'public';

    public bool $debugEnabled = false;

    public bool $onlineListEnabled = true;

    public bool $autoOpenIncomingPrivateChat = false;

    public array $onlineUsers = [];

    public array $conversations = [];

    public array $conversationUpdatedAtById = [];

    public bool $conversationSnapshotReady = false;

    public ?int $selectedConversationId = null;

    public function mount(string $mode = 'public', ?int $selectedConversationId = null): void
    {
        $this->mode = $mode;
        $this->debugEnabled = (bool) config('app.debug', false);
        $this->onlineListEnabled = (bool) config('chat.online_list_enabled', true);
        $this->autoOpenIncomingPrivateChat = (bool) config('chat.auto_open_incoming_private_chat', false);
        $this->selectedConversationId = $selectedConversationId;

        $this->refreshOnlineUsers();
        $this->refreshConversations(false);
        $this->conversationSnapshotReady = true;
    }

    public function refreshSidebarData(): void
    {
        $this->refreshOnlineUsers();
        $this->refreshConversations(true);
    }

    public function getListeners(): array
    {
        return [
            'sidebar-private-chat-opened' => 'handlePrivateChatOpened',
            'chat-shell-conversation-selected' => 'setSelectedConversation',
            'chat-shell-conversation-selected.main' => 'setSelectedConversation',
            'chat-shell-conversation-selected.modal' => 'setSelectedConversation',
        ];
    }

    public function refreshOnlineUsers(): void
    {
        if (! $this->onlineListEnabled || ! auth()->check()) {
            $this->onlineUsers = [];

            return;
        }

        $presenceService = app(PresenceService::class);
        $authenticatedUserId = (int) auth()->id();

        $users = $presenceService->onlineUsersQuery()
            ->whereKeyNot($authenticatedUserId)
            ->limit(25)
            ->get();

        $this->onlineUsers = $users
            ->map(function (User $user): array {
                return [
                    'id' => (int) $user->getKey(),
                    'chat_id' => (string) $user->chat_id,
                    'status' => $user->last_seen_at ? $user->last_seen_at->diffForHumans() : __('chat.sidebar.active_now'),
                ];
            })
            ->values()
            ->all();
    }

    public function refreshConversations(bool $checkForIncomingActivity = true): void
    {
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            $this->conversations = [];
            $this->conversationUpdatedAtById = [];

            return;
        }

        $conversations = Conversation::query()
            ->where('type', 'private')
            ->whereHas('users', static fn ($query) => $query->whereKey($authenticatedUser->getKey()))
            ->with(['users' => static fn ($query) => $query->select('users.id', 'chat_id')])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $conversationItems = $conversations
            ->map(function (Conversation $conversation) use ($authenticatedUser): array {
                $peer = $conversation->users->firstWhere('id', '!=', $authenticatedUser->getKey());

                return [
                    'conversation_id' => (int) $conversation->getKey(),
                    'is_passphrase' => (bool) $conversation->is_passphrase,
                    'peer_user_id' => (int) ($peer?->getKey() ?? 0),
                    'peer_chat_id' => (string) ($peer?->chat_id ?? __('chat.common.unknown')),
                    'updated_at' => optional($conversation->updated_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        if ($checkForIncomingActivity && $this->conversationSnapshotReady && $this->autoOpenIncomingPrivateChat) {
            $incomingConversationId = $this->detectIncomingConversationId($conversationItems);

            if ($incomingConversationId) {
                $this->setSelectedConversation($incomingConversationId);
                $this->dispatch('chat-shell-select-conversation', conversationId: $incomingConversationId)
                    ->to(component: ChatShellLivewire::class);
            }
        }

        $this->conversations = $conversationItems;
        $this->conversationUpdatedAtById = collect($conversationItems)
            ->mapWithKeys(static fn (array $conversation): array => [
                (string) $conversation['conversation_id'] => (string) ($conversation['updated_at'] ?? ''),
            ])
            ->all();
    }

    public function startPrivateChat(int $targetUserId, bool $isPassphrase, ConversationService $conversationService): void
    {
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $targetUser = User::query()->findOrFail($targetUserId);

        $this->emitDebug('start_private_chat_called', [
            'target_user_id' => $targetUserId,
            'is_passphrase' => $isPassphrase,
        ]);

        $conversation = $conversationService->startPrivateChat(
            opener: $authenticatedUser,
            target: $targetUser,
            isPassphrase: $isPassphrase,
        );

        $this->upsertConversation([
            'conversation_id' => (int) $conversation->getKey(),
            'is_passphrase' => $isPassphrase,
            'peer_user_id' => (int) $targetUser->getKey(),
            'peer_chat_id' => (string) $targetUser->chat_id,
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->setSelectedConversation((int) $conversation->getKey());
        $this->dispatch('chat-shell-select-conversation', conversationId: (int) $conversation->getKey())
            ->to(component: ChatShellLivewire::class);

        $this->emitDebug('start_private_chat_dispatched', [
            'conversation_id' => (int) $conversation->getKey(),
            'target_user_id' => $targetUserId,
            'is_passphrase' => $isPassphrase,
        ]);
    }

    public function openConversation(int $conversationId): void
    {
        $this->setSelectedConversation($conversationId);
        $this->dispatch('chat-shell-select-conversation', conversationId: $conversationId)
            ->to(component: ChatShellLivewire::class);
    }

    #[On('chat-shell-select-conversation')]
    public function setSelectedConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePrivateChatOpened(array $payload): void
    {
        $conversationId = (int) ($payload['conversation_id'] ?? 0);

        if ($conversationId <= 0) {
            return;
        }

        $this->upsertConversation([
            'conversation_id' => $conversationId,
            'is_passphrase' => (bool) ($payload['is_passphrase'] ?? false),
            'peer_user_id' => (int) ($payload['opener_user_id'] ?? 0),
            'peer_chat_id' => (string) ($payload['opener_chat_id'] ?? __('chat.common.unknown')),
            'updated_at' => (string) ($payload['opened_at'] ?? now()->toIso8601String()),
        ]);

        if ($this->autoOpenIncomingPrivateChat) {
            $this->setSelectedConversation($conversationId);
            $this->dispatch('chat-shell-select-conversation', conversationId: $conversationId)
                ->to(component: ChatShellLivewire::class);
        }
    }

    /**
     * @param  array{conversation_id:int,is_passphrase:bool,peer_user_id:int,peer_chat_id:string,updated_at:string}  $conversation
     */
    private function upsertConversation(array $conversation): void
    {
        $filtered = collect($this->conversations)
            ->reject(static fn (array $item): bool => (int) ($item['conversation_id'] ?? 0) === $conversation['conversation_id'])
            ->values()
            ->all();

        array_unshift($filtered, $conversation);

        $this->conversations = array_slice($filtered, 0, 20);
        $this->conversationUpdatedAtById[(string) $conversation['conversation_id']] = (string) $conversation['updated_at'];
    }

    /**
     * @param  array<int, array{conversation_id:int,is_passphrase:bool,peer_user_id:int,peer_chat_id:string,updated_at:string|null}>  $conversationItems
     */
    private function detectIncomingConversationId(array $conversationItems): ?int
    {
        foreach ($conversationItems as $conversationItem) {
            $conversationId = (int) $conversationItem['conversation_id'];
            $updatedAt = (string) ($conversationItem['updated_at'] ?? '');
            $previousUpdatedAt = (string) ($this->conversationUpdatedAtById[(string) $conversationId] ?? '');

            $hasChanged = $previousUpdatedAt === '' || $previousUpdatedAt !== $updatedAt;

            if ($hasChanged && $conversationId !== (int) $this->selectedConversationId) {
                return $conversationId;
            }
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.chat.sidebar-online-users-livewire');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function emitDebug(string $step, array $context = []): void
    {
        if (! $this->debugEnabled) {
            return;
        }

        $this->dispatch('chat-debug', scope: 'sidebar', step: $step, context: $context, ts: now()->toIso8601String());
    }
}
