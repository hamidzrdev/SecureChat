<?php

use App\Livewire\Chat\ChatShellLivewire;
use App\Livewire\Chat\SidebarOnlineUsersLivewire;
use App\Models\Conversation;
use App\Models\User;
use Livewire\Livewire;

test('sidebar component starts private chat and selects conversation', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Livewire::actingAs($userA)
        ->test(SidebarOnlineUsersLivewire::class, ['mode' => 'private'])
        ->call('startPrivateChat', $userB->getKey(), false)
        ->assertSet('selectedConversationId', fn ($value) => is_int($value) && $value > 0)
        ->assertSet('conversations.0.peer_user_id', $userB->getKey())
        ->assertSet('conversations.0.is_passphrase', false);
});

test('chat shell auto-opens incoming private chat when enabled', function () {
    config()->set('chat.auto_open_incoming_private_chat', true);

    $user = User::factory()->create();
    $opener = User::factory()->create();
    $conversation = Conversation::query()->create([
        'type' => 'private',
        'is_passphrase' => false,
        'pair_key' => collect([$user->getKey(), $opener->getKey()])->sort()->implode(':'),
    ]);
    $conversation->users()->sync([$user->getKey(), $opener->getKey()]);

    Livewire::actingAs($user)
        ->test(ChatShellLivewire::class, ['mode' => 'private'])
        ->call('handlePrivateChatOpened', [
            'conversation_id' => (int) $conversation->getKey(),
            'opener_user_id' => (int) $opener->getKey(),
            'opener_chat_id' => (string) $opener->chat_id,
            'is_passphrase' => false,
            'opened_at' => now()->toIso8601String(),
        ])
        ->assertSet('selectedConversationId', (int) $conversation->getKey())
        ->assertSet('modalConversationId', (int) $conversation->getKey())
        ->assertSet('privateConversationModalOpen', true);
});
