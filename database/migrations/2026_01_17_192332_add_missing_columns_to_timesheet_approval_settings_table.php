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
        Schema::table('timesheet_approval_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheet_approval_settings', 'company_id')) {
                $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->cascadeOnDelete();
            }
            
            if (!Schema::hasColumn('timesheet_approval_settings', 'branch_id')) {
                $table->foreignId('branch_id')->after('company_id')->nullable()->constrained('branches')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheet_approval_settings', function (Blueprint $table) {
            if (Schema::hasColumn('timesheet_approval_settings', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            
            if (Schema::hasColumn('timesheet_approval_settings', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
