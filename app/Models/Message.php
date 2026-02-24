<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'kind',
        'is_passphrase',
        'ciphertext',
        'crypto_meta',
        'attachment_path',
        'attachment_meta',
    ];

    protected function casts(): array
    {
        return [
            'is_passphrase' => 'boolean',
            'crypto_meta' => 'array',
            'attachment_meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
