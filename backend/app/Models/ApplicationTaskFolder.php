<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationTaskFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'parent_folder_id',
        'name',
        'color',
        'position',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_folder_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_folder_id')->orderBy('position')->orderBy('id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ApplicationTaskCategory::class, 'task_folder_id')->orderBy('position')->orderBy('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ApplicationTask::class, 'task_folder_id');
    }
}
