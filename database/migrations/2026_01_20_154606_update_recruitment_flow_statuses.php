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
        // Add status to interview records
        Schema::table('hr_interview_records', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_interview_records', 'status')) {
                $table->string('status', 50)->default('scheduled')->after('recommendation');
            }
            if (!Schema::hasColumn('hr_interview_records', 'response_notes')) {
                $table->text('response_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('hr_interview_records', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('response_notes');
            }
        });

        // The applicant statuses are strings, so we don't need a formal DB change for enums,
        // but we'll ensure they are handled in the models.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_interview_records', function (Blueprint $table) {
            $table->dropColumn(['status', 'response_notes', 'responded_at']);
        });
    }
};
