<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_profiles', 'segment_rank')) {
                $table->string('segment_rank')->default('Regular')->after('segment');
            }
        });

        if (Schema::hasColumn('customer_profiles', 'segment')) {
            DB::statement("UPDATE customer_profiles SET segment_rank = COALESCE(segment, 'Regular') WHERE segment_rank IS NULL OR segment_rank = ''");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_profiles', 'segment_rank')) {
                $table->dropColumn('segment_rank');
            }
        });
    }
};
