<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('application_tasks')
            || ! Schema::hasTable('application_task_folders')
            || ! Schema::hasTable('application_task_categories')
        ) {
            return;
        }

        $projectIds = DB::table('application_tasks')
            ->whereNotNull('project_id')
            ->distinct()
            ->pluck('project_id');

        foreach ($projectIds as $projectId) {
            $existingFolderId = DB::table('application_task_folders')
                ->where('project_id', $projectId)
                ->where('name', 'General')
                ->value('id');

            if ($existingFolderId) {
                $folderId = (int) $existingFolderId;
            } else {
                $createdBy = (int) (DB::table('projects')->where('id', $projectId)->value('company_user_id') ?? 0);
                if ($createdBy <= 0) {
                    continue;
                }

                $folderId = (int) DB::table('application_task_folders')->insertGetId([
                    'project_id' => $projectId,
                    'created_by_user_id' => $createdBy,
                    'name' => 'General',
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $existingCategoryId = DB::table('application_task_categories')
                ->where('task_folder_id', $folderId)
                ->where('name', 'General')
                ->value('id');

            if ($existingCategoryId) {
                $categoryId = (int) $existingCategoryId;
            } else {
                $createdBy = (int) (DB::table('application_task_folders')->where('id', $folderId)->value('created_by_user_id') ?? 0);
                if ($createdBy <= 0) {
                    continue;
                }

                $categoryId = (int) DB::table('application_task_categories')->insertGetId([
                    'project_id' => $projectId,
                    'task_folder_id' => $folderId,
                    'created_by_user_id' => $createdBy,
                    'name' => 'General',
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('application_tasks')
                ->where('project_id', $projectId)
                ->whereNull('task_folder_id')
                ->update(['task_folder_id' => $folderId]);

            DB::table('application_tasks')
                ->where('project_id', $projectId)
                ->whereNull('task_category_id')
                ->update(['task_category_id' => $categoryId]);
        }

        DB::table('application_tasks')
            ->select('id', 'project_id', 'status')
            ->orderBy('project_id')
            ->orderBy('status')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $positions = [];

                foreach ($rows as $row) {
                    $key = $row->project_id . '|' . $row->status;
                    $positions[$key] = ($positions[$key] ?? 0) + 1;

                    DB::table('application_tasks')
                        ->where('id', $row->id)
                        ->update(['position' => $positions[$key]]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally no-op: generated folders/categories may contain user-managed data.
    }
};
