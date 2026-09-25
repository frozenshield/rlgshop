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
        Schema::create('access_matrix', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('ref_staff_role')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('ref_module')->cascadeOnDelete();
            $table->unsignedBigInteger('sub_module_id')->nullable();
            $table->boolean('can_read')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'module_id', 'sub_module_id'], 'role_module_sub_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_matrix');
    }
};
