<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('application_task_folders', 'color')) {
            return;
        }

        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('application_task_folders', 'color')) {
            return;
        }

        Schema::table('application_task_folders', function (Blueprint $table): void {
            $table->string('color', 20)->nullable()->after('name');
        });
    }
};
