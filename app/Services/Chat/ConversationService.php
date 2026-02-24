<?php

namespace App\Services\Chat;

use App\Events\PrivateChatOpened;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function ensurePublicConversation(): Conversation
    {
        if (! config('chat.public_enabled')) {
            throw ValidationException::withMessages([
                'chat' => __('chat.errors.public_chat_disabled'),
            ]);
        }

        return Conversation::query()->firstOrCreate(
            [
                'type' => 'public',
                'is_passphrase' => false,
                'pair_key' => $this->publicPairKey(),
            ]
        );
    }

    public function startPrivateChat(User $opener, User $target, bool $isPassphrase = false): Conversation
    {
        if ($opener->is($target)) {
            throw ValidationException::withMessages([
                'target_user_id' => __('chat.errors.private_chat_with_self'),
            ]);
        }

        $conversation = DB::transaction(function () use ($opener, $target, $isPassphrase): Conversation {
            $pairKey = $this->buildPairKey($opener->getKey(), $target->getKey());

            $conversation = $this->findOrCreatePrivateConversation($pairKey, $isPassphrase);

            $conversation->users()->syncWithoutDetaching([
                $opener->getKey(),
                $target->getKey(),
            ]);

            return $conversation->loadMissing('users');
        });

        try {
            PrivateChatOpened::dispatch(
                $target->getKey(),
                $conversation->getKey(),
                $opener->getKey(),
                (string) $opener->chat_id,
                $isPassphrase,
            );
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return $conversation;
    }

    /**
     * @return array{verify_token:string, salt:string, iter:int, kdf:string, algo:string, conversation_id:int}
     */
    public function issuePassphraseVerifyChallenge(Conversation $conversation, User $user): array
    {
        $this->ensureConversationParticipant($conversation, $user);
        $this->ensurePassphraseConversation($conversation);

        if (! filled($conversation->passphrase_salt)) {
            $conversation->forceFill([
                'passphrase_salt' => base64_encode(random_bytes((int) config('chat.passphrase.salt_bytes', 16))),
            ])->save();
        }

        return [
            'verify_token' => base64_encode(random_bytes((int) config('chat.passphrase.verify_token_bytes', 32))),
            'salt' => (string) $conversation->passphrase_salt,
            'iter' => (int) config('chat.passphrase.kdf_iter', 150000),
            'kdf' => (string) config('chat.passphrase.kdf', 'PBKDF2'),
            'algo' => (string) config('chat.passphrase.algo', 'AES-GCM'),
            'conversation_id' => (int) $conversation->getKey(),
        ];
    }

    public function storePassphraseVerifyBlob(Conversation $conversation, User $user, string $verifyBlobBase64): Conversation
    {
        $this->ensureConversationParticipant($conversation, $user);
        $this->ensurePassphraseConversation($conversation);

        if (! $this->isBase64($verifyBlobBase64)) {
            throw ValidationException::withMessages([
                'verify_blob_base64' => __('chat.errors.invalid_verify_blob_format'),
            ]);
        }

        if (! filled($conversation->passphrase_salt)) {
            $conversation->passphrase_salt = base64_encode(random_bytes((int) config('chat.passphrase.salt_bytes', 16)));
        }

        $conversation->passphrase_verify_blob = $verifyBlobBase64;
        $conversation->save();

        return $conversation;
    }

    /**
     * @return array{conversation_id:int,salt:string,verify_blob:string,iter:int,kdf:string,algo:string,v:int}
     */
    public function getPassphraseMeta(Conversation $conversation, User $user): array
    {
        $this->ensureConversationParticipant($conversation, $user);
        $this->ensurePassphraseConversation($conversation);

        if (! filled($conversation->passphrase_salt) || ! filled($conversation->passphrase_verify_blob)) {
            throw ValidationException::withMessages([
                'passphrase' => __('chat.errors.passphrase_meta_not_configured'),
            ]);
        }

        return [
            'conversation_id' => (int) $conversation->getKey(),
            'salt' => (string) $conversation->passphrase_salt,
            'verify_blob' => (string) $conversation->passphrase_verify_blob,
            'iter' => (int) config('chat.passphrase.kdf_iter', 150000),
            'kdf' => (string) config('chat.passphrase.kdf', 'PBKDF2'),
            'algo' => (string) config('chat.passphrase.algo', 'AES-GCM'),
            'v' => 1,
        ];
    }

    public function ensureConversationParticipant(Conversation $conversation, User|Authenticatable|null $user): void
    {
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'auth' => __('chat.errors.auth_required'),
            ]);
        }

        if ($conversation->type === 'public') {
            if (! config('chat.public_enabled')) {
                throw ValidationException::withMessages([
                    'conversation' => __('chat.errors.public_chat_disabled'),
                ]);
            }

            return;
        }

        $isParticipant = $conversation->users()
            ->whereKey($user->getKey())
            ->exists();

        if (! $isParticipant) {
            throw ValidationException::withMessages([
                'conversation' => __('chat.errors.conversation_access_denied'),
            ]);
        }
    }

    private function findOrCreatePrivateConversation(string $pairKey, bool $isPassphrase): Conversation
    {
        try {
            return Conversation::query()->firstOrCreate(
                [
                    'type' => 'private',
                    'is_passphrase' => $isPassphrase,
                    'pair_key' => $pairKey,
                ]
            );
        } catch (QueryException) {
            return Conversation::query()
                ->where('type', 'private')
                ->where('is_passphrase', $isPassphrase)
                ->where('pair_key', $pairKey)
                ->firstOrFail();
        }
    }

    private function buildPairKey(int|string $leftUserId, int|string $rightUserId): string
    {
        $normalizedIds = [
            (string) $leftUserId,
            (string) $rightUserId,
        ];

        sort($normalizedIds, SORT_NATURAL);

        return implode(':', $normalizedIds);
    }

    private function publicPairKey(): string
    {
        return 'public:global';
    }

    private function ensurePassphraseConversation(Conversation $conversation): void
    {
        if (! (bool) $conversation->is_passphrase) {
            throw ValidationException::withMessages([
                'conversation' => __('chat.errors.passphrase_endpoint_only'),
            ]);
        }
    }

    private function isBase64(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === preg_replace('/\s+/', '', $value);
    }
}
