<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to change foreign keys
        // Skip if using SQLite as it doesn't support dropping foreign keys
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: Just ensure the column exists, FK constraints are less strict
            return;
        }

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            // Drop old FK to departments table if it exists, then point to hr_departments
            try {
                $table->dropForeign(['department_id']);
            } catch (\Throwable $e) {
                // Ignore if it does not exist (e.g. on fresh install)
            }

            $table->foreign('department_id')
                ->references('id')
                ->on('hr_departments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            try {
                $table->dropForeign(['department_id']);
            } catch (\Throwable $e) {
                // Ignore if already dropped
            }

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }
};


