<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_tasks', function (Blueprint $table): void {
            $table->foreignId('task_folder_id')
                ->nullable()
                ->after('project_id')
                ->constrained('application_task_folders')
                ->nullOnDelete();

            $table->foreignId('task_category_id')
                ->nullable()
                ->after('task_folder_id')
                ->constrained('application_task_categories')
                ->nullOnDelete();

            $table->unsignedInteger('position')
                ->default(0)
                ->after('status');

            $table->index(['project_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('application_tasks', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'status', 'position']);
            $table->dropConstrainedForeignId('task_category_id');
            $table->dropConstrainedForeignId('task_folder_id');
            $table->dropColumn('position');
        });
    }
};
