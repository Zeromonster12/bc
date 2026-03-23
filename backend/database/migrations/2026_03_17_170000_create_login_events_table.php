<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email_submitted_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_hash', 64)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('success');
            $table->string('reason_code', 64);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['success', 'occurred_at']);
            $table->index('reason_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_events');
    }
};
