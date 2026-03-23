<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
        ];
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

        if ($this->relationLoaded('studentProfile')) {
            $avatarPath = $this->studentProfile?->avatar_path;
        } elseif ($this->role === 'student') {
            $avatarPath = $this->studentProfile()->value('avatar_path');
        }

        if (is_string($avatarPath) && $avatarPath !== '') {
            return '/storage/' . $avatarPath;
        }

        return null;
    }
}
