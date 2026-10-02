<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_analytics_executive_report;');

        $sqlPath = database_path('migrations/stored proc/sp_get_analytics_executive_report.sql');
        if (! file_exists($sqlPath)) {
            $sqlPath = database_path('migrations/stored_proc/sp_get_analytics_executive_report.sql');
        }

        if (file_exists($sqlPath)) {
            DB::unprepared(file_get_contents($sqlPath));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_analytics_executive_report;');
    }
};

