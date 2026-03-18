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
        Schema::table('hr_overtime_requests', function (Blueprint $table) {
            // Remove columns that are now in hr_overtime_request_lines table
            if (Schema::hasColumn('hr_overtime_requests', 'overtime_hours')) {
                $table->dropColumn('overtime_hours');
            }
            if (Schema::hasColumn('hr_overtime_requests', 'overtime_rate')) {
                $table->dropColumn('overtime_rate');
            }
            if (Schema::hasColumn('hr_overtime_requests', 'day_type')) {
                $table->dropColumn('day_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_overtime_requests', function (Blueprint $table) {
            // Restore columns if rolling back
            if (!Schema::hasColumn('hr_overtime_requests', 'overtime_hours')) {
                $table->decimal('overtime_hours', 4, 2)->nullable()->after('overtime_date');
            }
            if (!Schema::hasColumn('hr_overtime_requests', 'overtime_rate')) {
                $table->decimal('overtime_rate', 5, 2)->default(1.50)->after('overtime_hours');
            }
            if (!Schema::hasColumn('hr_overtime_requests', 'day_type')) {
                $table->string('day_type', 50)->nullable()->after('overtime_rate');
            }
        });
    }
};
