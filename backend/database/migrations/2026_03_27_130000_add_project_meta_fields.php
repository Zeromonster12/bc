<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'location_strategy')) {
                $table->string('location_strategy', 20)->default('remote')->after('location');
            }

            if (! Schema::hasColumn('projects', 'industry')) {
                $table->string('industry', 120)->nullable()->after('location_strategy');
            }

            if (! Schema::hasColumn('projects', 'internship_duration')) {
                $table->string('internship_duration', 120)->nullable()->after('industry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('projects', 'internship_duration')) {
                $dropColumns[] = 'internship_duration';
            }

            if (Schema::hasColumn('projects', 'industry')) {
                $dropColumns[] = 'industry';
            }

            if (Schema::hasColumn('projects', 'location_strategy')) {
                $dropColumns[] = 'location_strategy';
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
