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
        // Check if columns already exist
        if (Schema::hasColumn('hr_positions', 'position_code')) {
            return;
        }

        Schema::table('hr_positions', function (Blueprint $table) {
            // Add position control fields
            $table->string('position_code', 50)->nullable()->after('department_id');
            $table->string('position_title', 200)->nullable()->after('position_code');
            $table->text('job_description')->nullable()->after('position_title');
            $table->foreignId('grade_id')->nullable()->after('job_description')->constrained('hr_job_grades')->onDelete('set null');
            $table->integer('approved_headcount')->default(1)->after('grade_id');
            $table->integer('filled_headcount')->default(0)->after('approved_headcount');
            $table->decimal('budgeted_salary', 15, 2)->nullable()->after('filled_headcount');
            $table->string('status', 50)->default('approved')->after('budgeted_salary'); // 'approved', 'frozen', 'cancelled'
            $table->date('effective_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('effective_date');

            // Add indexes
            $table->index('position_code');
            $table->index('status');
            $table->index(['effective_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_positions', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropIndex(['position_code']);
            $table->dropIndex(['status']);
            $table->dropIndex(['effective_date', 'end_date']);
            
            $table->dropColumn([
                'position_code',
                'position_title',
                'job_description',
                'grade_id',
                'approved_headcount',
                'filled_headcount',
                'budgeted_salary',
                'status',
                'effective_date',
                'end_date'
            ]);
        });
    }
};
