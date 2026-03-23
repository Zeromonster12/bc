<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_submitted_hash',
        'ip_hash',
        'user_agent',
        'device_hash',
        'country_code',
        'success',
        'reason_code',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
