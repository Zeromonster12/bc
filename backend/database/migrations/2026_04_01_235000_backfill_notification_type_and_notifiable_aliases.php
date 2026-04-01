<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_USER_CLASS = 'App\\Models\\User';
    private const USER_ALIAS = 'user';

    private const LEGACY_MESSAGE_TYPE = 'App\\Notifications\\NewMessageReceivedNotification';
    private const MESSAGE_TYPE_ALIAS = 'message.received';

    private const LEGACY_COMPANY_TYPE = 'App\\Notifications\\CompanyApprovalStatusNotification';
    private const COMPANY_APPROVED_ALIAS = 'company.approved';
    private const COMPANY_REJECTED_ALIAS = 'company.rejected';

    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('type', self::LEGACY_MESSAGE_TYPE)
            ->update(['type' => self::MESSAGE_TYPE_ALIAS]);

        DB::table('notifications')
            ->where('notifiable_type', self::LEGACY_USER_CLASS)
            ->update(['notifiable_type' => self::USER_ALIAS]);

        $legacyCompanyRows = DB::table('notifications')
            ->where('type', self::LEGACY_COMPANY_TYPE)
            ->select(['id', 'data'])
            ->get();

        foreach ($legacyCompanyRows as $row) {
            $resolvedType = $this->resolveCompanyTypeAlias($row->data);

            if ($resolvedType === null) {
                continue;
            }

            DB::table('notifications')
                ->where('id', $row->id)
                ->update(['type' => $resolvedType]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('type', self::MESSAGE_TYPE_ALIAS)
            ->update(['type' => self::LEGACY_MESSAGE_TYPE]);

        DB::table('notifications')
            ->whereIn('type', [
                self::COMPANY_APPROVED_ALIAS,
                self::COMPANY_REJECTED_ALIAS,
            ])
            ->update(['type' => self::LEGACY_COMPANY_TYPE]);

        DB::table('notifications')
            ->where('notifiable_type', self::USER_ALIAS)
            ->update(['notifiable_type' => self::LEGACY_USER_CLASS]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $rawData): array
    {
        if (is_array($rawData)) {
            return $rawData;
        }

        if (!is_string($rawData) || trim($rawData) === '') {
            return [];
        }

        $decoded = json_decode($rawData, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveCompanyTypeAlias(mixed $rawData): ?string
    {
        $payload = $this->decodePayload($rawData);
        $kind = strtolower(trim((string) ($payload['kind'] ?? '')));
        $status = strtolower(trim((string) ($payload['company_verification_status'] ?? '')));

        if ($kind === self::COMPANY_APPROVED_ALIAS || $status === 'approved') {
            return self::COMPANY_APPROVED_ALIAS;
        }

        if ($kind === self::COMPANY_REJECTED_ALIAS || $status === 'rejected') {
            return self::COMPANY_REJECTED_ALIAS;
        }

        return null;
    }
};
