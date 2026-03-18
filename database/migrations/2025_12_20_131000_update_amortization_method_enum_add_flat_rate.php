<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: SQLite uses string columns, so enum values are already allowed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        // Skip for SQLite - string columns accept any value
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Extend amortization_method enum to include flat_rate (MySQL only)
        DB::statement("
            ALTER TABLE loans 
            MODIFY COLUMN amortization_method 
            ENUM('annuity', 'straight_principal', 'interest_only', 'flat_rate') 
            NOT NULL DEFAULT 'annuity'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        // Skip for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Revert to original enum without flat_rate
        DB::statement("
            ALTER TABLE loans 
            MODIFY COLUMN amortization_method 
            ENUM('annuity', 'straight_principal', 'interest_only') 
            NOT NULL DEFAULT 'annuity'
        ");
    }
};


