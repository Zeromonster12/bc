<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            if (! Schema::hasColumn('conversations', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            if (Schema::hasColumn('conversations', 'avatar_path')) {
                $table->dropColumn('avatar_path');
            }
        });
    }
};
