<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fixes the effective_interest_rate column size to accommodate larger EIR values.
     * The EIR calculator returns percentage values (e.g., 10.0 = 10%, 1000 = 1000%),
     * but the original column was decimal(5,2) which can only store up to 999.99.
     * We increase it to decimal(8,4) to allow up to 9999.9999% with better precision.
     */
    public function up(): void
    {
        // Update loans table
        if (Schema::hasTable('loans') && Schema::hasColumn('loans', 'effective_interest_rate')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->decimal('effective_interest_rate', 8, 4)->nullable()->change();
            });
        }
        
        // Update loan_ifrs_schedules table (if it exists)
        if (Schema::hasTable('loan_ifrs_schedules') && Schema::hasColumn('loan_ifrs_schedules', 'effective_interest_rate')) {
            Schema::table('loan_ifrs_schedules', function (Blueprint $table) {
                $table->decimal('effective_interest_rate', 8, 4)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert loans table
        if (Schema::hasTable('loans') && Schema::hasColumn('loans', 'effective_interest_rate')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->decimal('effective_interest_rate', 5, 2)->nullable()->change();
            });
        }
        
        // Revert loan_ifrs_schedules table (if it exists)
        if (Schema::hasTable('loan_ifrs_schedules') && Schema::hasColumn('loan_ifrs_schedules', 'effective_interest_rate')) {
            Schema::table('loan_ifrs_schedules', function (Blueprint $table) {
                $table->decimal('effective_interest_rate', 5, 2)->nullable()->change();
            });
        }
    }
};
