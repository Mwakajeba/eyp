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
        Schema::table('asset_revaluations', function (Blueprint $table) {
            // Add approval workflow fields if they don't exist
            if (!Schema::hasColumn('asset_revaluations', 'current_approval_level')) {
                $table->unsignedTinyInteger('current_approval_level')->nullable()->after('status')
                    ->comment('Current approval level being processed');
            }
            
            if (!Schema::hasColumn('asset_revaluations', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('current_approval_level')
                    ->constrained('users')->onDelete('set null')
                    ->comment('User who submitted for approval');
            }
            
            if (!Schema::hasColumn('asset_revaluations', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by')
                    ->comment('When revaluation was submitted for approval');
            }
            
            if (!Schema::hasColumn('asset_revaluations', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')
                    ->constrained('users')->onDelete('set null')
                    ->comment('User who rejected the revaluation');
            }
            
            if (!Schema::hasColumn('asset_revaluations', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by')
                    ->comment('When revaluation was rejected');
            }
            
            if (!Schema::hasColumn('asset_revaluations', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at')
                    ->comment('Reason for rejection');
            }
            
            // Add indexes for faster lookups
            if (!Schema::hasColumn('asset_revaluations', 'current_approval_level')) {
                $table->index('current_approval_level');
            }
            if (!Schema::hasColumn('asset_revaluations', 'submitted_by')) {
                $table->index('submitted_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_revaluations', function (Blueprint $table) {
            if (Schema::hasColumn('asset_revaluations', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('asset_revaluations', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('asset_revaluations', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('asset_revaluations', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('asset_revaluations', 'submitted_by')) {
                $table->dropForeign(['submitted_by']);
                $table->dropIndex(['submitted_by']);
                $table->dropColumn('submitted_by');
            }
            if (Schema::hasColumn('asset_revaluations', 'current_approval_level')) {
                $table->dropIndex(['current_approval_level']);
                $table->dropColumn('current_approval_level');
            }
        });
    }
};
