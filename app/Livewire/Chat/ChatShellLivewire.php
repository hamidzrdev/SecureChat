<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\PresenceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChatShellLivewire extends Component
{
    public string $mode = 'public';

    public bool $debugEnabled = false;

    public bool $publicEnabled = true;

    public bool $onlineListEnabled = true;

    public bool $imagesEnabled = true;

    public bool $autoOpenIncomingPrivateChat = false;

    public ?int $publicConversationId = null;

    public ?int $selectedConversationId = null;

    public ?int $modalConversationId = null;

    public bool $privateConversationModalOpen = false;

    public bool $modalConversationPassphrase = false;

    public function mount(string $mode = 'public', ?int $conversation = null): void
    {
        $conversationService = app(ConversationService::class);

        $this->mode = $mode;
        $this->debugEnabled = (bool) config('app.debug', false);
        $this->publicEnabled = (bool) config('chat.public_enabled', true);
        $this->onlineListEnabled = (bool) config('chat.online_list_enabled', true);
        $this->imagesEnabled = (bool) config('chat.images_enabled', true);
        $this->autoOpenIncomingPrivateChat = (bool) config('chat.auto_open_incoming_private_chat', false);

        if ($this->mode === 'public') {
            if (! $this->publicEnabled) {
                abort(404);
            }

            $publicConversation = $conversationService->ensurePublicConversation();
            $this->publicConversationId = (int) $publicConversation->getKey();
            $this->selectedConversationId = $this->publicConversationId;
        } elseif ($conversation) {
            $conversationModel = Conversation::query()->findOrFail($conversation);
            $conversationService->ensureConversationParticipant($conversationModel, auth()->user());

            if ($this->mode === 'passphrase' && ! (bool) $conversationModel->is_passphrase) {
                abort(404);
            }

            if ($this->mode === 'private' && (bool) $conversationModel->is_passphrase) {
                abort(404);
            }

            if ($conversationModel->type === 'private') {
                $this->openPrivateConversationModal((int) $conversationModel->getKey(), (bool) $conversationModel->is_passphrase);
            } else {
                $this->selectedConversationId = (int) $conversationModel->getKey();
            }
        }
    }

    public function getListeners(): array
    {
        return [
            'chat-shell-select-conversation' => 'selectConversation',
            'reverb-private-chat-opened' => 'handlePrivateChatOpened',
            'reverb-private-message-received' => 'handlePrivateMessageReceived',
            'reverb-message-sent' => 'handleConversationMessage',
        ];
    }

    public function pingPresence(PresenceService $presenceService): void
    {
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            return;
        }

        $presenceService->touch($authenticatedUser, (string) session()->getId());
    }

    public function selectConversation(int $conversationId, ConversationService $conversationService): void
    {
        $this->emitDebug('select_conversation_called', [
            'conversation_id' => $conversationId,
        ]);

        $conversation = Conversation::query()->findOrFail($conversationId);
        $conversationService->ensureConversationParticipant($conversation, auth()->user());

        if ($conversation->type === 'private') {
            $this->openPrivateConversationModal((int) $conversation->getKey(), (bool) $conversation->is_passphrase);

            return;
        }

        $this->selectedConversationId = (int) $conversation->getKey();
        $this->dispatch('chat-shell-conversation-selected.main', conversationId: $this->selectedConversationId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePrivateChatOpened(array $payload): void
    {
        $conversationId = (int) ($payload['conversation_id'] ?? 0);

        $this->dispatch('sidebar-private-chat-opened', payload: $payload);

        if ($conversationId > 0 && $this->autoOpenIncomingPrivateChat) {
            $this->openPrivateConversationModal($conversationId, (bool) ($payload['is_passphrase'] ?? false));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleConversationMessage(array $payload): void
    {
        $conversationId = (int) ($payload['conversation_id'] ?? 0);

        if ($conversationId <= 0) {
            return;
        }

        if ($this->privateConversationModalOpen && $this->modalConversationId === $conversationId) {
            $this->dispatch('conversation-realtime-message.modal', payload: $payload);

            return;
        }

        if ($this->selectedConversationId === $conversationId) {
            $this->dispatch('conversation-realtime-message.main', payload: $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePrivateMessageReceived(array $payload): void
    {
        $conversationId = (int) ($payload['conversation_id'] ?? 0);

        if ($conversationId <= 0) {
            return;
        }

        $this->dispatch('sidebar-private-chat-opened', payload: [
            'conversation_id' => $conversationId,
            'is_passphrase' => (bool) ($payload['is_passphrase'] ?? false),
            'opener_user_id' => (int) ($payload['sender_user_id'] ?? 0),
            'opener_chat_id' => (string) ($payload['sender_chat_id'] ?? __('chat.common.unknown')),
            'opened_at' => (string) ($payload['received_at'] ?? now()->toIso8601String()),
        ]);

        $this->openPrivateConversationModal($conversationId, (bool) ($payload['is_passphrase'] ?? false));
        $this->dispatch('conversation-realtime-message.modal', payload: [
            'conversation_id' => $conversationId,
        ]);
    }

    public function closePrivateConversationModal(): void
    {
        $this->emitDebug('close_private_modal_called', [
            'conversation_id' => $this->modalConversationId,
        ]);

        $this->privateConversationModalOpen = false;
        $this->modalConversationId = null;
        $this->modalConversationPassphrase = false;

        if ($this->mode === 'public' && $this->publicConversationId) {
            $this->selectedConversationId = $this->publicConversationId;
            $this->dispatch('chat-shell-conversation-selected.main', conversationId: $this->publicConversationId);
        } else {
            $this->selectedConversationId = null;
        }
    }

    public function updatedSelectedConversationId(): void
    {
        $this->dispatch(
            'chat-shell-reconfigure-realtime',
            user_id: auth()->id(),
            conversation_id: $this->selectedConversationId,
        );
    }

    public function render(): View
    {
        return view('livewire.chat.chat-shell-livewire');
    }

    private function openPrivateConversationModal(int $conversationId, ?bool $isPassphrase = null): void
    {
        if ($this->privateConversationModalOpen && $this->modalConversationId === $conversationId) {
            $this->selectedConversationId = $conversationId;

            return;
        }

        $conversationService = app(ConversationService::class);
        $conversation = Conversation::query()->findOrFail($conversationId);

        $conversationService->ensureConversationParticipant($conversation, auth()->user());

        if ($conversation->type !== 'private') {
            $this->selectedConversationId = (int) $conversation->getKey();
            $this->dispatch('chat-shell-conversation-selected.main', conversationId: $this->selectedConversationId);

            return;
        }

        $this->modalConversationId = (int) $conversation->getKey();
        $this->modalConversationPassphrase = $isPassphrase ?? (bool) $conversation->is_passphrase;
        $this->privateConversationModalOpen = true;
        $this->selectedConversationId = $this->modalConversationId;

        $this->emitDebug('private_modal_opened', [
            'conversation_id' => $this->modalConversationId,
            'is_passphrase' => $this->modalConversationPassphrase,
        ]);

        $this->dispatch('chat-shell-conversation-selected.modal', conversationId: $this->modalConversationId);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function emitDebug(string $step, array $context = []): void
    {
        if (! $this->debugEnabled) {
            return;
        }

        $this->dispatch('chat-debug', scope: 'chat-shell', step: $step, context: $context, ts: now()->toIso8601String());
    }
}
