<?php

$toBool = static function (string $key, bool $default): bool {
    $value = env($key, $default);
    $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    return $parsed ?? $default;
};

$toCsvArray = static function (string $value): array {
    $items = array_map('trim', explode(',', $value));

    return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
};

return [
    'ttl_minutes' => (int) env('CHAT_TTL_MINUTES', 120),
    'public_enabled' => $toBool('CHAT_PUBLIC_ENABLED', true),
    'online_list_enabled' => $toBool('CHAT_ONLINE_LIST_ENABLED', true),
    'images_enabled' => $toBool('CHAT_IMAGES_ENABLED', true),
    'allowed_mime_types' => $toCsvArray((string) env('CHAT_ALLOWED_MIME_TYPES', 'image/jpeg,image/png,image/webp,image/gif')),
    'allowed_extensions' => $toCsvArray((string) env('CHAT_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,webp,gif')),
    'max_image_kb' => (int) env('CHAT_MAX_IMAGE_KB', 2048),
    'max_text_length' => (int) env('CHAT_MAX_TEXT_LENGTH', 2000),
    'online_window_minutes' => (int) env('CHAT_ONLINE_WINDOW_MINUTES', 5),
    'auto_open_incoming_private_chat' => $toBool('CHAT_AUTO_OPEN_INCOMING_PRIVATE_CHAT', false),
    'attachments' => [
        'disk' => (string) env('CHAT_ATTACHMENT_DISK', 'local'),
        'signed_url_ttl_minutes' => (int) env('CHAT_ATTACHMENT_URL_TTL_MINUTES', 10),
    ],
    'passphrase' => [
        'verify_token_bytes' => (int) env('CHAT_PASSPHRASE_VERIFY_TOKEN_BYTES', 32),
        'salt_bytes' => (int) env('CHAT_PASSPHRASE_SALT_BYTES', 16),
        'kdf' => (string) env('CHAT_PASSPHRASE_KDF', 'PBKDF2'),
        'algo' => (string) env('CHAT_PASSPHRASE_ALGO', 'AES-GCM'),
        'kdf_iter' => (int) env('CHAT_PASSPHRASE_KDF_ITER', 150000),
    ],
    'rate_limits' => [
        'login_per_minute' => (int) env('CHAT_RATE_LIMIT_LOGIN_PER_MINUTE', 10),
        'message_per_minute' => (int) env('CHAT_RATE_LIMIT_MESSAGE_PER_MINUTE', 30),
    ],
];
