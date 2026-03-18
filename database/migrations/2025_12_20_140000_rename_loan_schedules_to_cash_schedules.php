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
        // Rename existing loan_schedules to loan_cash_schedules (contractual schedule) if not already renamed
        if (Schema::hasTable('loan_schedules') && !Schema::hasTable('loan_cash_schedules')) {
            Schema::rename('loan_schedules', 'loan_cash_schedules');
        }
        
        // Add period tracking fields for cash schedule (only if they don't exist)
        if (Schema::hasTable('loan_cash_schedules')) {
            Schema::table('loan_cash_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('loan_cash_schedules', 'period_start')) {
                    $table->date('period_start')->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('loan_cash_schedules', 'period_end')) {
                    $table->date('period_end')->nullable()->after('period_start');
                }
                if (!Schema::hasColumn('loan_cash_schedules', 'period_no')) {
                    $table->integer('period_no')->nullable()->after('installment_no');
                }
                if (!Schema::hasColumn('loan_cash_schedules', 'opening_balance')) {
                    $table->decimal('opening_balance', 15, 2)->nullable()->after('opening_principal');
                }
                if (!Schema::hasColumn('loan_cash_schedules', 'closing_balance')) {
                    $table->decimal('closing_balance', 15, 2)->nullable()->after('closing_principal');
                }
                if (!Schema::hasColumn('loan_cash_schedules', 'schedule_type')) {
                    $table->string('schedule_type')->default('cash')->after('status'); // 'cash' for contractual
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_cash_schedules', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end', 'period_no', 'opening_balance', 'closing_balance', 'schedule_type']);
        });
        
        Schema::rename('loan_cash_schedules', 'loan_schedules');
    }
};

