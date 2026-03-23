<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_progress_updates') || ! Schema::hasTable('application_tasks')) {
            return;
        }

        DB::table('application_progress_updates')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                $applicationIds = $rows->pluck('application_id')->unique()->values();
                $applications = DB::table('applications')
                    ->whereIn('id', $applicationIds)
                    ->get(['id', 'project_id', 'student_user_id'])
                    ->keyBy('id');

                $inserts = [];

                foreach ($rows as $row) {
                    $application = $applications->get($row->application_id);
                    if (! $application) {
                        continue;
                    }

                    $status = match ($row->student_project_status) {
                        'completed' => 'complete',
                        'in_progress', 'blocked' => 'in_progress',
                        default => 'todo',
                    };

                    $inserts[] = [
                        'application_id' => $row->application_id,
                        'project_id' => $application->project_id,
                        'created_by_user_id' => $row->student_user_id,
                        'assignee_user_id' => $application->student_user_id,
                        'title' => $row->title,
                        'requirements' => null,
                        'priority' => 'medium',
                        'status' => $status,
                        'student_note' => $row->notes,
                        'due_at' => null,
                        'completed_at' => $status === 'complete' ? $row->created_at : null,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($inserts !== []) {
                    DB::table('application_tasks')->insert($inserts);
                }
            });
    }

    public function down(): void
    {
        // Intentionally left empty. Backfilled rows are historical records and
        // cannot be safely distinguished from user-created rows in all cases.
    }
};
