<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const COMPANY_STATUS_PENDING = 'pending';
    public const COMPANY_STATUS_APPROVED = 'approved';
    public const COMPANY_STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'company_verification_status',
        'company_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_verified_at' => 'datetime',
        ];
    }

    public function isCompanyVerified(): bool
    {
        if ($this->role !== 'company') {
            return true;
        }

        return $this->company_verification_status === self::COMPANY_STATUS_APPROVED;
    }

    public function getNameAttribute($value): string
    {
        $firstName = trim((string) ($this->attributes['first_name'] ?? ''));
        $lastName = trim((string) ($this->attributes['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) $value;
    }

    public function companyProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'company_user_id');
    }

    public function studentApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'student_user_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function githubAccount(): HasOne
    {
        return $this->hasOne(SocialAccount::class)->where('provider', 'github');
    }

    public function cvFiles(): HasMany
    {
        return $this->hasMany(StudentCvFile::class, 'student_user_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function chatConversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_message_id', 'last_read_at'])
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }

    public function conversationParticipantRecords(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatarPath = null;
        $signedRouteName = null;

        if ($this->role === 'company') {
            if ($this->relationLoaded('companyProfile')) {
                $avatarPath = $this->companyProfile?->logo_path;
            } else {
                $avatarPath = $this->companyProfile()->value('logo_path');
            }
            $signedRouteName = 'users.company-logo.signed';
        } else {
            if ($this->relationLoaded('studentProfile')) {
                $avatarPath = $this->studentProfile?->avatar_path;
            } elseif ($this->role === 'student') {
                $avatarPath = $this->studentProfile()->value('avatar_path');
            }
            $signedRouteName = 'users.avatar.signed';
        }

        if (is_string($avatarPath) && $avatarPath !== '') {
            $ttlMinutes = max(1, (int) config('filesystems.avatar_temporary_url_minutes', 60));

            return URL::temporarySignedRoute(
                $signedRouteName,
                now()->addMinutes($ttlMinutes),
                ['user' => $this->id]
            );
        }

        return null;
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }
}
