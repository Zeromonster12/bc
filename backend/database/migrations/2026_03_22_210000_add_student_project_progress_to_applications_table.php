<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('student_project_status', 32)
                ->nullable()
                ->after('status');
            $table->string('student_project_note', 1000)
                ->nullable()
                ->after('student_project_status');
            $table->timestamp('student_project_status_updated_at')
                ->nullable()
                ->after('student_project_note');

            $table->index(['student_user_id', 'status'], 'applications_student_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_student_status_index');
            $table->dropColumn([
                'student_project_status',
                'student_project_note',
                'student_project_status_updated_at',
            ]);
        });
    }
};
