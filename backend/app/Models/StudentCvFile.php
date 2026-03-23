<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCvFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_user_id',
        'storage_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'scan_status',
        'scan_message',
        'scanned_at',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
