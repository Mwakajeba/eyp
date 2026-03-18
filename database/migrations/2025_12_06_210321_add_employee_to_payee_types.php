<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: payee_type columns already support string values, so 'employee' is automatically allowed.
     * We only need to add the employee_id foreign key columns.
     */
    public function up(): void
    {
        // Update petty_cash_transactions table
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            // Add employee_id column
            if (!Schema::hasColumn('petty_cash_transactions', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('set null');
            }
        });
        
        // Update payments table
        Schema::table('payments', function (Blueprint $table) {
            // Add employee_id column
            if (!Schema::hasColumn('payments', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('set null');
            }
        });
        
        // Update receipts table
        Schema::table('receipts', function (Blueprint $table) {
            // Add employee_id column
            if (!Schema::hasColumn('receipts', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert petty_cash_transactions
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('petty_cash_transactions', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
        
        // Revert payments
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
        
        // Revert receipts
        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
    }
};
