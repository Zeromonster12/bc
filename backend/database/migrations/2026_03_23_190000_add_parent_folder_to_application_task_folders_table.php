<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->foreignId('parent_folder_id')
                ->nullable()
                ->after('created_by_user_id')
                ->constrained('application_task_folders')
                ->nullOnDelete();

            $table->index(['project_id', 'parent_folder_id', 'position'], 'atf_project_parent_position_idx');
        });
    }

    public function down(): void
    {
        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->dropIndex('atf_project_parent_position_idx');
            $table->dropConstrainedForeignId('parent_folder_id');
        });
    }
};
