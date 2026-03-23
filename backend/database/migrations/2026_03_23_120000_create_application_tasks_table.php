<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('requirements')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->string('status', 32)->default('todo');
            $table->string('student_note', 1000)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_tasks');
    }
};
