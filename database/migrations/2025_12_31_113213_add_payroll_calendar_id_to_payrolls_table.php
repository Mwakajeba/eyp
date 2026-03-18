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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('payroll_calendar_id')->nullable()->after('company_id')->constrained('hr_payroll_calendars')->onDelete('restrict');
            $table->index('payroll_calendar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['payroll_calendar_id']);
            $table->dropIndex(['payroll_calendar_id']);
            $table->dropColumn('payroll_calendar_id');
        });
    }
};
