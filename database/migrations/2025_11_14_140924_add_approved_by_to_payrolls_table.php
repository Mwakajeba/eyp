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
            if (!Schema::hasColumn('payrolls', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('is_fully_approved')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('payrolls', 'approved_at')) {
                $afterColumn = Schema::hasColumn('payrolls', 'approved_by') ? 'approved_by' : 'is_fully_approved';
                $table->timestamp('approved_at')->nullable()->after($afterColumn);
            }
            if (!Schema::hasColumn('payrolls', 'approval_remarks')) {
                $afterColumn = Schema::hasColumn('payrolls', 'approved_at') ? 'approved_at' : (Schema::hasColumn('payrolls', 'approved_by') ? 'approved_by' : 'is_fully_approved');
                $table->text('approval_remarks')->nullable()->after($afterColumn);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'approved_by')) {
                // Try to drop the foreign key only if it exists
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                    // Foreign key does not exist, ignore
                }
                $table->dropColumn(['approved_by', 'approved_at', 'approval_remarks']);
            }
        });
    }
};
