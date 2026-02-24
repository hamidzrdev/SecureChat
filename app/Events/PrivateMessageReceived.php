<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $recipientUserId,
        private readonly int $conversationId,
        private readonly int $senderUserId,
        private readonly string $senderChatId,
        private readonly bool $isPassphrase,
        private readonly int $messageId,
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
        return 'PrivateMessageReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_user_id' => $this->senderUserId,
            'sender_chat_id' => $this->senderChatId,
            'is_passphrase' => $this->isPassphrase,
            'message_id' => $this->messageId,
            'received_at' => now()->toIso8601String(),
        ];
    }
}
