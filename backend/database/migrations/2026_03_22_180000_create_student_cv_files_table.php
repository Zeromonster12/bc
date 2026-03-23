<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_cv_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('original_filename', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['student_user_id', 'uploaded_at']);
            $table->index('checksum_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_cv_files');
    }
};
