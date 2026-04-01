<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('application_progress_updates');
    }

    public function down(): void
    {
        if (Schema::hasTable('application_progress_updates')) {
            return;
        }

        Schema::create('application_progress_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('notes')->nullable();
            $table->string('student_project_status', 32)->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });
    }
};
