<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'is_passphrase',
        'pair_key',
        'passphrase_salt',
        'passphrase_verify_blob',
    ];

    protected function casts(): array
    {
        return [
            'is_passphrase' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
