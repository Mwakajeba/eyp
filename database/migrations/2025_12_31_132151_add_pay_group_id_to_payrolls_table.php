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
            $table->foreignId('pay_group_id')->nullable()->after('payroll_calendar_id')
                ->constrained('hr_pay_groups')->onDelete('set null');
            $table->index('pay_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['pay_group_id']);
            $table->dropIndex(['pay_group_id']);
            $table->dropColumn('pay_group_id');
        });
    }
};
