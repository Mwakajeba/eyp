<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates the effective_interest_rate column in loan_ifrs_schedules table
     * to decimal(8,4) if it's still the old size (5,2).
     */
    public function up(): void
    {
        if (Schema::hasTable('loan_ifrs_schedules') && Schema::hasColumn('loan_ifrs_schedules', 'effective_interest_rate')) {
            // Check current column definition
            $columnInfo = DB::select("SHOW COLUMNS FROM loan_ifrs_schedules WHERE Field = 'effective_interest_rate'");
            
            if (!empty($columnInfo)) {
                $type = $columnInfo[0]->Type;
                // If it's still decimal(5,2), update it
                if (strpos($type, 'decimal(5,2)') !== false) {
                    Schema::table('loan_ifrs_schedules', function (Blueprint $table) {
                        $table->decimal('effective_interest_rate', 8, 4)->nullable()->change();
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't revert - keep the larger column size
    }
};
