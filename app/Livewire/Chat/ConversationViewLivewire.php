<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Contracts\View\View;
use JsonException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConversationViewLivewire extends Component
{
    use WithFileUploads;

    public string $mode = 'public';

    public string $context = 'main';

    public ?int $conversationId = null;

    public bool $passphraseMode = false;

    public bool $imagesEnabled = true;

    public bool $passphraseConfigured = false;

    public array $messages = [];

    public string $draftText = '';

    public string $passphraseCiphertextBase64 = '';

    public string $passphraseCryptoMetaJson = '';

    public mixed $image = null;

    public ?array $passphraseMeta = null;

    public function mount(string $mode = 'public', string $context = 'main', ?int $conversationId = null): void
    {
        $conversationService = app(ConversationService::class);

        $this->mode = $mode;
        $this->context = $context;
        $this->imagesEnabled = (bool) config('chat.images_enabled', true);
        $this->conversationId = $conversationId;

        if ($this->mode === 'public' && ! $this->conversationId && config('chat.public_enabled')) {
            $publicConversation = $conversationService->ensurePublicConversation();
            $this->conversationId = (int) $publicConversation->getKey();
        }

        if ($this->conversationId) {
            $this->reloadMessages($conversationService);
        }
    }

    public function getListeners(): array
    {
        return [
            "chat-shell-conversation-selected.{$this->context}" => 'onConversationSelected',
            "conversation-realtime-message.{$this->context}" => 'onRealtimeMessage',
            "conversation-force-refresh.{$this->context}" => 'forceRefreshConversation',
        ];
    }

    public function onConversationSelected(int $conversationId): void
    {
        $conversationService = app(ConversationService::class);

        $this->conversationId = $conversationId;
        $this->reloadMessages($conversationService);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function onRealtimeMessage(array $payload): void
    {
        $conversationService = app(ConversationService::class);

        if ((int) ($payload['conversation_id'] ?? 0) !== $this->conversationId) {
            return;
        }

        $this->reloadMessages($conversationService);
    }

    public function pollMessages(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversationService = app(ConversationService::class);
        $this->reloadMessages($conversationService);
    }

    public function forceRefreshConversation(int $conversationId): void
    {
        if (! $this->conversationId || $conversationId !== $this->conversationId) {
            return;
        }

        $conversationService = app(ConversationService::class);
        $this->reloadMessages($conversationService);
    }

    public function sendText(MessageService $messageService, ConversationService $conversationService): void
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation) {
            return;
        }

        $payload = $this->passphraseMode
            ? $this->passphrasePayload()
            : ['text' => $this->draftText];

        $message = $messageService->sendTextMessage(
            $conversation,
            $this->authenticatedUser(),
            $payload,
        );

        $this->messages[] = $messageService->toClientMessage($message);
        $this->draftText = '';
        $this->passphraseCiphertextBase64 = '';
        $this->passphraseCryptoMetaJson = '';
        $this->dispatchScrollToBottom(focusComposer: true, clearComposerDraft: true);
    }

    public function sendImage(MessageService $messageService, ConversationService $conversationService): void
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation || ! $this->image) {
            return;
        }

        $this->validate([
            'image' => ['file', 'max:'.((int) config('chat.max_image_kb', 2048))],
        ]);

        $message = $messageService->sendImageMessage(
            $conversation,
            $this->authenticatedUser(),
            $this->image,
            [],
        );

        $this->messages[] = $messageService->toClientMessage($message);
        $this->reset('image');
        $this->dispatchScrollToBottom(focusComposer: true);
    }

    public function clearImage(): void
    {
        $this->reset('image');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function requestPassphraseMeta(ConversationService $conversationService): ?array
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation || ! $this->passphraseMode || ! $this->passphraseConfigured) {
            return null;
        }

        $this->passphraseMeta = $conversationService->getPassphraseMeta($conversation, $this->authenticatedUser());

        return $this->passphraseMeta;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function issuePassphraseChallenge(ConversationService $conversationService): ?array
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation || ! $this->passphraseMode) {
            return null;
        }

        $challenge = $conversationService->issuePassphraseVerifyChallenge($conversation, $this->authenticatedUser());

        return $challenge;
    }

    public function storePassphraseVerifyBlob(string $verifyBlobBase64, ConversationService $conversationService): void
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation || ! $this->passphraseMode) {
            return;
        }

        $conversationService->storePassphraseVerifyBlob(
            $conversation,
            $this->authenticatedUser(),
            $verifyBlobBase64,
        );

        $this->passphraseConfigured = true;
    }

    private function reloadMessages(ConversationService $conversationService): void
    {
        $conversation = $this->resolveConversation($conversationService);

        if (! $conversation instanceof Conversation) {
            $this->messages = [];
            $this->passphraseMode = false;
            $this->passphraseConfigured = false;
            $this->dispatchScrollToBottom();

            return;
        }

        $messageService = app(MessageService::class);
        $this->passphraseMode = (bool) $conversation->is_passphrase;
        $this->passphraseConfigured = $this->passphraseMode
            && filled($conversation->passphrase_salt)
            && filled($conversation->passphrase_verify_blob);
        $this->messages = $messageService
            ->listConversationMessages($conversation, $this->authenticatedUser(), 150)
            ->values()
            ->all();
        $this->dispatchScrollToBottom();
    }

    private function dispatchScrollToBottom(bool $focusComposer = false, bool $clearComposerDraft = false): void
    {
        $this->dispatch(
            'conversation-scroll-to-bottom',
            context: $this->context,
            conversationId: $this->conversationId,
            focusComposer: $focusComposer,
            clearComposerDraft: $clearComposerDraft,
        );
    }

    private function resolveConversation(ConversationService $conversationService): ?Conversation
    {
        if (! $this->conversationId) {
            return null;
        }

        $conversation = Conversation::query()->find($this->conversationId);

        if (! $conversation instanceof Conversation) {
            return null;
        }

        $conversationService->ensureConversationParticipant($conversation, $this->authenticatedUser());

        return $conversation;
    }

    /**
     * @return array<string, mixed>
     */
    private function passphrasePayload(): array
    {
        try {
            $cryptoMeta = json_decode($this->passphraseCryptoMetaJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $cryptoMeta = [];
        }

        return [
            'ciphertext_base64' => $this->passphraseCiphertextBase64,
            'crypto_meta' => is_array($cryptoMeta) ? $cryptoMeta : [],
        ];
    }

    private function authenticatedUser(): User
    {
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        return $authenticatedUser;
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-view-livewire');
    }
}
