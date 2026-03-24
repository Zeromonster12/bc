<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->string('status', 20)->nullable()->after('position');
            $table->index(['project_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'status', 'position']);
            $table->dropColumn('status');
        });
    }
};
