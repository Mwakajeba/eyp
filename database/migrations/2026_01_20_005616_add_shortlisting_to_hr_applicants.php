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
        Schema::table('hr_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_applicants', 'is_shortlisted')) {
                $table->boolean('is_shortlisted')->default(false)->after('status');
            }
            if (!Schema::hasColumn('hr_applicants', 'shortlisted_at')) {
                $table->timestamp('shortlisted_at')->nullable()->after('is_shortlisted');
            }
            if (!Schema::hasColumn('hr_applicants', 'shortlisted_by')) {
                $table->foreignId('shortlisted_by')->nullable()->constrained('users')->onDelete('set null')->after('shortlisted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_applicants', function (Blueprint $table) {
            $table->dropColumn(['is_shortlisted', 'shortlisted_at', 'shortlisted_by']);
        });
    }
};
