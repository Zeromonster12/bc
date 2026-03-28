<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('conversation_participants', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (Schema::hasColumn('conversation_participants', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });
    }
};
