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
            if (!Schema::hasColumn('hr_applicants', 'qualifications')) {
                $table->json('qualifications')->nullable()->after('qualification');
            }
            if (!Schema::hasColumn('hr_applicants', 'qualification_documents')) {
                $table->json('qualification_documents')->nullable()->after('qualifications');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('hr_applicants', 'qualifications')) {
                $table->dropColumn('qualifications');
            }
            if (Schema::hasColumn('hr_applicants', 'qualification_documents')) {
                $table->dropColumn('qualification_documents');
            }
        });
    }
};
