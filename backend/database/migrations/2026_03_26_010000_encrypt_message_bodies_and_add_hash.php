<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->string('body_hash', 64)->nullable()->after('body');
            $table->index('body_hash');
        });

        DB::table('messages')
            ->select(['id', 'body'])
            ->orderBy('id')
            ->chunkById(200, function ($messages): void {
                foreach ($messages as $message) {
                    $plainText = is_string($message->body) ? $message->body : '';

                    try {
                        $plainText = Crypt::decryptString($plainText);
                    } catch (\Throwable) {
                        // Message row is still plaintext.
                    }

                    DB::table('messages')
                        ->where('id', $message->id)
                        ->update([
                            'body' => Crypt::encryptString($plainText),
                            'body_hash' => hash_hmac('sha256', $plainText, (string) config('app.key')),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex(['body_hash']);
            $table->dropColumn('body_hash');
        });
    }
};
