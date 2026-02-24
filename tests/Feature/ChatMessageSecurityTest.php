<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

test('normal chat text is encrypted at rest and decrypted on retrieval', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $conversationId = (int) $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => false,
        ])
        ->assertSuccessful()
        ->json('conversation_id');

    $this->actingAs($userA)
        ->post(route('chat.messages.send-text', ['conversation' => $conversationId]), [
            'text' => 'hello normal chat',
        ])
        ->assertCreated();

    $message = Message::query()->latest('id')->firstOrFail();
    expect((string) $message->ciphertext)->not->toContain('hello normal chat');
    expect((string) ($message->crypto_meta['mode'] ?? null))->toBe('server');

    $messagesResponse = $this->actingAs($userB)
        ->get(route('chat.messages.index', ['conversation' => $conversationId]))
        ->assertSuccessful()
        ->json('messages');

    expect((string) ($messagesResponse[0]['plaintext']['text'] ?? ''))->toBe('hello normal chat');
});

test('passphrase text is stored as provided ciphertext and not decrypted by server', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $conversationId = (int) $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => true,
        ])
        ->assertSuccessful()
        ->json('conversation_id');

    $ciphertextBase64 = base64_encode(random_bytes(32));
    $saltBase64 = base64_encode(random_bytes(16));
    $ivBase64 = base64_encode(random_bytes(12));

    $this->actingAs($userA)
        ->post(route('chat.messages.send-text', ['conversation' => $conversationId]), [
            'ciphertext_base64' => $ciphertextBase64,
            'crypto_meta' => [
                'mode' => 'passphrase',
                'algo' => 'AES-GCM',
                'v' => 1,
                'iv' => $ivBase64,
                'salt' => $saltBase64,
                'kdf' => 'PBKDF2',
                'iter' => 150000,
            ],
        ])
        ->assertCreated();

    $message = Message::query()->latest('id')->firstOrFail();
    expect((string) $message->ciphertext)->toBe($ciphertextBase64);
    expect((string) ($message->crypto_meta['mode'] ?? null))->toBe('passphrase');

    $messagesResponse = $this->actingAs($userB)
        ->get(route('chat.messages.index', ['conversation' => $conversationId]))
        ->assertSuccessful()
        ->json('messages');

    expect((string) ($messagesResponse[0]['ciphertext_base64'] ?? ''))->toBe($ciphertextBase64);
    expect(array_key_exists('plaintext', $messagesResponse[0]))->toBeFalse();
});

test('passphrase verification blob challenge and meta endpoints work', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $conversationId = (int) $this
        ->actingAs($userA)
        ->post(route('chat.private.start'), [
            'target_user_id' => $userB->getKey(),
            'is_passphrase' => true,
        ])
        ->assertSuccessful()
        ->json('conversation_id');

    $challengeResponse = $this->actingAs($userA)
        ->post(route('chat.passphrase.challenge', ['conversation' => $conversationId]))
        ->assertSuccessful()
        ->json();

    expect((string) ($challengeResponse['salt'] ?? ''))->not->toBe('');
    expect((string) ($challengeResponse['verify_token'] ?? ''))->not->toBe('');
    expect((int) ($challengeResponse['iter'] ?? 0))->toBe(150000);

    $verifyBlob = base64_encode(random_bytes(48));

    $this->actingAs($userA)
        ->put(route('chat.passphrase.store-verify-blob', ['conversation' => $conversationId]), [
            'verify_blob_base64' => $verifyBlob,
            'iter' => 150000,
        ])
        ->assertSuccessful();

    $metaResponse = $this->actingAs($userB)
        ->get(route('chat.passphrase.meta', ['conversation' => $conversationId]))
        ->assertSuccessful()
        ->json();

    expect((string) ($metaResponse['verify_blob'] ?? ''))->toBe($verifyBlob);
    expect((string) ($metaResponse['salt'] ?? ''))->not->toBe('');
});

test('ttl cleanup command deletes expired messages and attachments', function () {
    config()->set('chat.ttl_minutes', 1);
    config()->set('chat.attachments.disk', 'local');

    Storage::fake('local');

    $conversation = Conversation::query()->create([
        'type' => 'private',
        'is_passphrase' => false,
        'pair_key' => '1:2',
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation->users()->sync([$userA->getKey(), $userB->getKey()]);

    $attachmentPath = 'chat-images/test/expired-image.jpg';
    Storage::disk('local')->put($attachmentPath, 'expired-image-content');

    $message = Message::query()->create([
        'conversation_id' => $conversation->getKey(),
        'sender_id' => $userA->getKey(),
        'kind' => 'image',
        'is_passphrase' => false,
        'ciphertext' => 'fake-cipher',
        'crypto_meta' => ['mode' => 'server', 'algo' => 'laravel-crypt', 'v' => 1],
        'attachment_path' => $attachmentPath,
        'attachment_meta' => [
            'disk' => 'local',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1200,
            'original_name' => 'expired-image.jpg',
        ],
    ]);

    $message->forceFill([
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ])->saveQuietly();

    expect(Storage::disk('local')->exists($attachmentPath))->toBeTrue();

    Artisan::call('chat:cleanup-expired');

    expect(Message::query()->whereKey($message->getKey())->exists())->toBeFalse();
    expect(Storage::disk('local')->exists($attachmentPath))->toBeFalse();
});
