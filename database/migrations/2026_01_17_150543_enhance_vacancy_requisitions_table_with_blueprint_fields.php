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
        Schema::table('hr_vacancy_requisitions', function (Blueprint $table) {
            // Hiring justification and financial control
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'hiring_justification')) {
                $table->text('hiring_justification')->nullable()->after('requirements');
            }
            
            // Cost center and budget line for financial control
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('department_id')
                    ->constrained('hr_departments')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'budget_line_id')) {
                $table->foreignId('budget_line_id')->nullable()->after('cost_center_id')
                    ->constrained('budget_lines')->onDelete('set null');
            }
            
            // Donor/project linkage
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'project_grant_code')) {
                $table->string('project_grant_code', 100)->nullable()->after('budget_line_id');
            }
            
            // Contract period
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'contract_period_months')) {
                $table->integer('contract_period_months')->nullable()->after('project_grant_code');
            }
            
            // Recruitment type (internal/external/both)
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'recruitment_type')) {
                $table->enum('recruitment_type', ['internal', 'external', 'both'])->default('external')
                    ->after('contract_period_months');
            }
            
            // Public posting fields
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'is_publicly_posted')) {
                $table->boolean('is_publicly_posted')->default(false)->after('recruitment_type');
            }
            
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'posting_start_date')) {
                $table->date('posting_start_date')->nullable()->after('is_publicly_posted');
            }
            
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'posting_end_date')) {
                $table->date('posting_end_date')->nullable()->after('posting_start_date');
            }
            
            // Add indexes (skip if columns don't exist or indexes already exist)
            try {
                if (Schema::hasColumn('hr_vacancy_requisitions', 'recruitment_type')) {
                    $table->index('recruitment_type', 'vr_recruitment_type_idx');
                }
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            
            try {
                if (Schema::hasColumn('hr_vacancy_requisitions', 'is_publicly_posted')) {
                    $table->index('is_publicly_posted', 'vr_is_publicly_posted_idx');
                }
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_vacancy_requisitions', function (Blueprint $table) {
            // Drop indexes
            try {
                $table->dropIndex('vr_recruitment_type_idx');
            } catch (\Exception $e) {
                // Index might not exist
            }
            
            try {
                $table->dropIndex('vr_is_publicly_posted_idx');
            } catch (\Exception $e) {
                // Index might not exist
            }
            
            // Drop foreign keys
            if (Schema::hasColumn('hr_vacancy_requisitions', 'cost_center_id')) {
                try {
                    $table->dropForeign(['cost_center_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
            if (Schema::hasColumn('hr_vacancy_requisitions', 'budget_line_id')) {
                try {
                    $table->dropForeign(['budget_line_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
            
            // Drop columns
            $columnsToDrop = [];
            $columns = [
                'hiring_justification',
                'cost_center_id',
                'budget_line_id',
                'project_grant_code',
                'contract_period_months',
                'recruitment_type',
                'is_publicly_posted',
                'posting_start_date',
                'posting_end_date',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('hr_vacancy_requisitions', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
