<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'project_id',
        'task_folder_id',
        'task_category_id',
        'created_by_user_id',
        'assignee_user_id',
        'title',
        'requirements',
        'priority',
        'status',
        'position',
        'student_note',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ApplicationTaskFolder::class, 'task_folder_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ApplicationTaskCategory::class, 'task_category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }
}
