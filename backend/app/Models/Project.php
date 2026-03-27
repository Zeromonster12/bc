<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_user_id',
        'title',
        'description',
        'requirements',
        'location',
        'location_strategy',
        'industry',
        'internship_duration',
        'tech_stack',
        'status',
        'max_students',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
        ];
    }

    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function taskFolders(): HasMany
    {
        return $this->hasMany(ApplicationTaskFolder::class)->orderBy('position')->orderBy('id');
    }

    public function taskCategories(): HasMany
    {
        return $this->hasMany(ApplicationTaskCategory::class)->orderBy('position')->orderBy('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ApplicationTask::class)->orderBy('position')->orderBy('id');
    }
}
