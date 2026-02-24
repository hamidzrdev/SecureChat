<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateChatOpened implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $recipientUserId,
        private readonly int $conversationId,
        private readonly int $openerUserId,
        private readonly string $openerChatId,
        private readonly bool $isPassphrase,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('private-user.'.$this->recipientUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PrivateChatOpened';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'opener_user_id' => $this->openerUserId,
            'opener_chat_id' => $this->openerChatId,
            'is_passphrase' => $this->isPassphrase,
            'opened_at' => now()->toIso8601String(),
        ];
    }
}
