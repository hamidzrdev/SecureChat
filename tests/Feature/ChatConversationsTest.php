<?php

use App\Events\PrivateChatOpened;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('starting private chat creates one normal conversation per user pair and broadcasts event', function () {
    Event::fake([PrivateChatOpened::class]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $firstResponse = $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => false,
        ]);

    $firstResponse
        ->assertSuccessful()
        ->assertJsonStructure([
            'conversation_id',
            'type',
            'is_passphrase',
            'target_user_id',
            'target_chat_id',
            'opened_at',
        ]);

    $firstConversationId = (int) $firstResponse->json('conversation_id');

    $secondResponse = $this
        ->actingAs($userB)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userA->getKey(),
            'is_passphrase' => false,
        ]);

    $secondResponse->assertSuccessful();

    expect((int) $secondResponse->json('conversation_id'))->toBe($firstConversationId);

    $conversation = Conversation::query()->findOrFail($firstConversationId);
    expect($conversation->type)->toBe('private');
    expect((bool) $conversation->is_passphrase)->toBeFalse();
    expect($conversation->users()->count())->toBe(2);

    Event::assertDispatched(PrivateChatOpened::class, 2);
});

test('starting passphrase private chat creates separate conversation for same user pair', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $normalResponse = $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => false,
        ]);

    $passphraseResponse = $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => true,
        ]);

    $normalResponse->assertSuccessful();
    $passphraseResponse->assertSuccessful();

    expect((int) $normalResponse->json('conversation_id'))
        ->not->toBe((int) $passphraseResponse->json('conversation_id'));

    expect(Conversation::query()->where('type', 'private')->count())->toBe(2);
});

test('public chat route lazily ensures exactly one shared public conversation', function () {
    config()->set('chat.public_enabled', true);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('chat.public'))->assertSuccessful();
    $this->actingAs($user)->get(route('chat.public'))->assertSuccessful();

    expect(
        Conversation::query()
            ->where('type', 'public')
            ->where('is_passphrase', false)
            ->count()
    )->toBe(1);
});
