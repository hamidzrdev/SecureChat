<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.public', static function (User $user): bool {
    return config('chat.public_enabled');
});

Broadcast::channel('chat.conversation.{conversation}', static function (User $user, Conversation $conversation): bool {
    return $conversation->users()->whereKey($user->getKey())->exists();
});

Broadcast::channel('chat.online', static function (User $user): array|bool {
    if (! config('chat.online_list_enabled')) {
        return false;
    }

    return [
        'id' => $user->getKey(),
        'chat_id' => $user->chat_id,
    ];
});

Broadcast::channel('private-user.{id}', static function (User $user, int $id): bool {
    return (int) $user->getKey() === $id;
});

Broadcast::channel('private-conversation.{conversationId}', static function (User $user, int $conversationId): bool {
    $conversation = Conversation::query()->find($conversationId);

    if (! $conversation instanceof Conversation) {
        return false;
    }

    if ($conversation->type === 'public') {
        return (bool) config('chat.public_enabled');
    }

    return $conversation->users()
        ->whereKey($user->getKey())
        ->exists();
});
