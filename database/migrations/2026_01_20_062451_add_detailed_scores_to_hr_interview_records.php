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
        Schema::table('hr_interview_records', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_interview_records', 'detailed_scores')) {
                $table->json('detailed_scores')->nullable()->after('overall_score');
            }
            if (!Schema::hasColumn('hr_interview_records', 'panel_comments')) {
                $table->json('panel_comments')->nullable()->after('feedback');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_interview_records', function (Blueprint $table) {
            $table->dropColumn(['detailed_scores', 'panel_comments']);
        });
    }
};
