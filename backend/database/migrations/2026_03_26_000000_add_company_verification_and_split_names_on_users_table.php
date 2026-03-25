<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('company_verification_status', 32)
                ->default(User::COMPANY_STATUS_APPROVED)
                ->after('role');
            $table->timestamp('company_verified_at')->nullable()->after('company_verification_status');
        });

        DB::table('users')->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $name = trim((string) ($user->name ?? ''));
                $firstName = '';
                $lastName = '';

                if ($name !== '') {
                    $parts = preg_split('/\s+/', $name, 2) ?: [];
                    $firstName = (string) ($parts[0] ?? '');
                    $lastName = (string) ($parts[1] ?? '');
                }

                $isCompany = ($user->role ?? '') === 'company';
                $status = $isCompany
                    ? User::COMPANY_STATUS_PENDING
                    : User::COMPANY_STATUS_APPROVED;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'company_verification_status' => $status,
                        'company_verified_at' => $status === User::COMPANY_STATUS_APPROVED ? now() : null,
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'company_verification_status',
                'company_verified_at',
            ]);
        });
    }
};
