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
        Schema::table('hfs_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('hfs_requests', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('current_approval_level')
                    ->constrained('users')->onDelete('set null')
                    ->comment('User who submitted for approval');
                
                $table->index('submitted_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hfs_requests', function (Blueprint $table) {
            if (Schema::hasColumn('hfs_requests', 'submitted_by')) {
                $table->dropForeign(['submitted_by']);
                $table->dropIndex(['submitted_by']);
                $table->dropColumn('submitted_by');
            }
        });
    }
};
