<?php

namespace App\Services\Chat;

use App\Events\MessageSent;
use App\Events\PrivateMessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class MessageService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendTextMessage(Conversation $conversation, User $sender, array $payload): Message
    {
        $this->ensureSenderBelongsToConversation($conversation, $sender);

        [$ciphertext, $cryptoMeta] = $conversation->is_passphrase
            ? $this->preparePassphrasePayload($payload)
            : $this->prepareServerPayload($payload);

        $message = Message::query()->create([
            'conversation_id' => $conversation->getKey(),
            'sender_id' => $sender->getKey(),
            'kind' => 'text',
            'is_passphrase' => (bool) $conversation->is_passphrase,
            'ciphertext' => $ciphertext,
            'crypto_meta' => $cryptoMeta,
            'attachment_path' => null,
            'attachment_meta' => null,
        ]);

        $this->dispatchMessageEvents($conversation, $message, $sender);

        return $message;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function sendImageMessage(Conversation $conversation, User $sender, UploadedFile $uploadedFile, array $meta = []): Message
    {
        $this->ensureSenderBelongsToConversation($conversation, $sender);

        if (! config('chat.images_enabled')) {
            throw ValidationException::withMessages([
                'image' => __('chat.errors.image_uploads_disabled'),
            ]);
        }

        $mimeType = (string) $uploadedFile->getMimeType();
        $extension = Str::lower((string) $uploadedFile->getClientOriginalExtension());
        $allowedMimeTypes = (array) config('chat.allowed_mime_types', []);
        $allowedExtensions = array_map(
            static fn (string $item): string => Str::lower($item),
            (array) config('chat.allowed_extensions', [])
        );

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'image' => __('chat.errors.image_mime_not_allowed'),
            ]);
        }

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'image' => __('chat.errors.image_extension_not_allowed'),
            ]);
        }

        $maxImageKb = (int) config('chat.max_image_kb', 2048);
        $sizeBytes = (int) $uploadedFile->getSize();
        $sizeKb = (int) ceil($sizeBytes / 1024);

        if ($sizeKb > $maxImageKb) {
            throw ValidationException::withMessages([
                'image' => __('chat.errors.image_size_exceeded'),
            ]);
        }

        $disk = (string) config('chat.attachments.disk', 'local');
        $attachmentPath = $uploadedFile->store('chat-images/'.now()->format('Y/m'), $disk);

        $attachmentMeta = array_merge([
            'disk' => $disk,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'size_bytes' => $sizeBytes,
            'size_kb' => $sizeKb,
        ], $meta);

        $ciphertext = $this->encryptServerPayload([
            'kind' => 'image',
            'attachment_path' => $attachmentPath,
            'attachment_meta' => $attachmentMeta,
            'v' => 1,
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->getKey(),
            'sender_id' => $sender->getKey(),
            'kind' => 'image',
            'is_passphrase' => (bool) $conversation->is_passphrase,
            'ciphertext' => $ciphertext,
            'crypto_meta' => [
                'mode' => 'server',
                'algo' => 'laravel-crypt',
                'v' => 1,
                'e2e' => false,
            ],
            'attachment_path' => $attachmentPath,
            'attachment_meta' => $attachmentMeta,
        ]);

        $this->dispatchMessageEvents($conversation, $message, $sender);

        return $message;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listConversationMessages(Conversation $conversation, User $viewer, int $limit = 100): Collection
    {
        $this->ensureSenderBelongsToConversation($conversation, $viewer);

        return Message::query()
            ->where('conversation_id', $conversation->getKey())
            ->with(['sender:id,chat_id'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message): array => $this->toClientMessage($message));
    }

    /**
     * @return array<string, mixed>
     */
    public function toClientMessage(Message $message): array
    {
        $message->loadMissing('sender:id,chat_id');

        $cryptoMeta = is_array($message->crypto_meta) ? $message->crypto_meta : [];
        $mode = (string) ($cryptoMeta['mode'] ?? 'unknown');

        $payload = [
            'id' => (int) $message->getKey(),
            'conversation_id' => (int) $message->conversation_id,
            'sender_id' => (int) $message->sender_id,
            'sender_chat_id' => (string) ($message->sender?->chat_id ?? ''),
            'kind' => (string) $message->kind,
            'is_passphrase' => (bool) $message->is_passphrase,
            'crypto_meta' => $cryptoMeta,
            'created_at' => optional($message->created_at)->toIso8601String(),
            'created_at_relative' => $this->formatRelativeCreatedAt($message->created_at),
        ];

        if ($mode === 'server') {
            $payload['plaintext'] = $this->decryptServerPayload($message->ciphertext);
        } else {
            $payload['ciphertext_base64'] = $message->ciphertext;
        }

        if ($message->kind === 'image') {
            $attachmentMeta = is_array($message->attachment_meta) ? $message->attachment_meta : [];
            $ttlMinutes = (int) config('chat.attachments.signed_url_ttl_minutes', 10);

            $attachmentMeta['access_url'] = URL::temporarySignedRoute(
                'chat.messages.attachment',
                now()->addMinutes($ttlMinutes),
                [
                    'message' => $message->getKey(),
                ]
            );

            $payload['attachment_meta'] = $attachmentMeta;
        }

        return $payload;
    }

    private function ensureSenderBelongsToConversation(Conversation $conversation, User $sender): void
    {
        if ($conversation->type === 'public') {
            if (! config('chat.public_enabled')) {
                throw ValidationException::withMessages([
                    'conversation' => __('chat.errors.public_chat_disabled'),
                ]);
            }

            return;
        }

        $isMember = $conversation->users()
            ->whereKey($sender->getKey())
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'sender_id' => __('chat.errors.sender_not_member'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:string,1:array<string,mixed>}
     */
    private function prepareServerPayload(array $payload): array
    {
        $text = trim((string) ($payload['text'] ?? ''));
        $maxLength = (int) config('chat.max_text_length', 2000);

        if ($text === '') {
            throw ValidationException::withMessages([
                'text' => __('chat.errors.message_text_empty'),
            ]);
        }

        if (mb_strlen($text) > $maxLength) {
            throw ValidationException::withMessages([
                'text' => __('chat.errors.message_text_too_long'),
            ]);
        }

        $ciphertext = $this->encryptServerPayload([
            'kind' => 'text',
            'text' => $text,
            'v' => 1,
        ]);

        return [
            $ciphertext,
            [
                'mode' => 'server',
                'algo' => 'laravel-crypt',
                'v' => 1,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:string,1:array<string,mixed>}
     */
    private function preparePassphrasePayload(array $payload): array
    {
        $ciphertext = (string) ($payload['ciphertext_base64'] ?? '');
        $cryptoMeta = is_array($payload['crypto_meta'] ?? null) ? $payload['crypto_meta'] : [];

        if (! $this->isBase64($ciphertext)) {
            throw ValidationException::withMessages([
                'ciphertext_base64' => __('chat.errors.invalid_ciphertext_payload'),
            ]);
        }

        $requiredKeys = ['mode', 'algo', 'v', 'iv', 'salt', 'kdf', 'iter'];
        foreach ($requiredKeys as $requiredKey) {
            if (! array_key_exists($requiredKey, $cryptoMeta)) {
                throw ValidationException::withMessages([
                    'crypto_meta' => __('chat.errors.invalid_passphrase_crypto_meta'),
                ]);
            }
        }

        if (
            $cryptoMeta['mode'] !== 'passphrase' ||
            $cryptoMeta['algo'] !== (string) config('chat.passphrase.algo', 'AES-GCM') ||
            (int) $cryptoMeta['v'] !== 1 ||
            $cryptoMeta['kdf'] !== (string) config('chat.passphrase.kdf', 'PBKDF2') ||
            (int) $cryptoMeta['iter'] !== (int) config('chat.passphrase.kdf_iter', 150000)
        ) {
            throw ValidationException::withMessages([
                'crypto_meta' => __('chat.errors.unsupported_passphrase_crypto_meta'),
            ]);
        }

        if (! $this->isBase64((string) $cryptoMeta['iv']) || ! $this->isBase64((string) $cryptoMeta['salt'])) {
            throw ValidationException::withMessages([
                'crypto_meta' => __('chat.errors.invalid_passphrase_crypto_meta'),
            ]);
        }

        return [
            $ciphertext,
            $cryptoMeta,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encryptServerPayload(array $payload): string
    {
        try {
            $plaintext = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'payload' => __('chat.errors.unable_to_encode_payload'),
            ]);
        }

        return Crypt::encryptString($plaintext);
    }

    /**
     * @return array<string, mixed>
     */
    private function decryptServerPayload(string $ciphertext): array
    {
        try {
            $plaintext = Crypt::decryptString($ciphertext);
            $decoded = json_decode($plaintext, true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return [
                'invalid' => true,
            ];
        }

        return is_array($decoded) ? $decoded : [];
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

    private function dispatchMessageEvents(Conversation $conversation, Message $message, User $sender): void
    {
        $conversation->touch();

        try {
            MessageSent::dispatch($message);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        if ($conversation->type !== 'private') {
            return;
        }

        $recipientUsers = $conversation->users()
            ->whereKeyNot($sender->getKey())
            ->get(['users.id', 'chat_id']);

        foreach ($recipientUsers as $recipientUser) {
            try {
                PrivateMessageReceived::dispatch(
                    (int) $recipientUser->getKey(),
                    (int) $conversation->getKey(),
                    (int) $sender->getKey(),
                    (string) $sender->chat_id,
                    (bool) $conversation->is_passphrase,
                    (int) $message->getKey(),
                );
            } catch (Throwable $throwable) {
                report($throwable);
            }
        }
    }

    private function formatRelativeCreatedAt(?CarbonInterface $createdAt): string
    {
        if (! $createdAt instanceof CarbonInterface) {
            return '';
        }

        $seconds = (int) floor((float) max(0, $createdAt->diffInSeconds(now())));
        $locale = app()->getLocale();

        if ($locale === 'fa') {
            if ($seconds < 10) {
                return 'الان';
            }

            if ($seconds < 60) {
                return $seconds.' ثانیه پیش';
            }

            if ($seconds < 3600) {
                return (int) floor($seconds / 60).' دقیقه پیش';
            }

            if ($seconds < 86400) {
                return (int) floor($seconds / 3600).' ساعت پیش';
            }

            return (int) floor($seconds / 86400).' روز پیش';
        }

        if ($seconds < 10) {
            return 'just now';
        }

        if ($seconds < 60) {
            return $seconds.' seconds ago';
        }

        if ($seconds < 3600) {
            $minutes = (int) floor($seconds / 60);

            return $minutes.' '.($minutes === 1 ? 'minute' : 'minutes').' ago';
        }

        if ($seconds < 86400) {
            $hours = (int) floor($seconds / 3600);

            return $hours.' '.($hours === 1 ? 'hour' : 'hours').' ago';
        }

        $days = (int) floor($seconds / 86400);

        return $days.' '.($days === 1 ? 'day' : 'days').' ago';
    }
}
