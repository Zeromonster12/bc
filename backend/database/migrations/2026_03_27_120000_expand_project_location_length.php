<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'location')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE projects MODIFY location VARCHAR(255) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE projects ALTER COLUMN location TYPE VARCHAR(255)');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE projects ALTER COLUMN location NVARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'location')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE projects MODIFY location VARCHAR(120) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE projects ALTER COLUMN location TYPE VARCHAR(120)');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE projects ALTER COLUMN location NVARCHAR(120) NULL');
        }
    }
};
