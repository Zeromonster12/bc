<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cv_files', function (Blueprint $table): void {
            $table->string('scan_status', 32)->default('pending')->after('checksum_sha256');
            $table->string('scan_message', 1000)->nullable()->after('scan_status');
            $table->timestamp('scanned_at')->nullable()->after('scan_message');

            $table->index(['student_user_id', 'scan_status']);
        });
    }

    public function down(): void
    {
        Schema::table('student_cv_files', function (Blueprint $table): void {
            $table->dropIndex(['student_user_id', 'scan_status']);
            $table->dropColumn(['scan_status', 'scan_message', 'scanned_at']);
        });
    }
};
