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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_type')) {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` VARCHAR(50) NOT NULL DEFAULT 'customer'");
            } else {
                $table->string('user_type', 50)->default('customer')->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_type')) {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer'");
            }
        });
    }
};
