<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Message;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly Message $message,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('private-conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->getKey(),
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'kind' => $this->message->kind,
            'is_passphrase' => (bool) $this->message->is_passphrase,
            'created_at' => optional($this->message->created_at)->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
