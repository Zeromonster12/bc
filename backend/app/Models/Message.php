<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'body',
        'body_hash',
    ];

    public function setBodyAttribute($value): void
    {
        $plainText = trim((string) $value);
        $this->attributes['body'] = Crypt::encryptString($plainText);
        $this->attributes['body_hash'] = hash_hmac('sha256', $plainText, (string) config('app.key'));
    }

    public function getBodyAttribute($value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Backward compatibility for legacy plaintext rows before encryption migration.
            return $value;
        }
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
