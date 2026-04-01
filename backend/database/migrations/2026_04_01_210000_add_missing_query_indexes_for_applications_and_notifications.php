<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->index(['project_id', 'status'], 'applications_project_status_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_notifiable_read_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_read_at_index');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_project_status_index');
        });
    }
};
