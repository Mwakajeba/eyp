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
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'published_to_portal')) {
                $table->boolean('published_to_portal')->default(false)->after('is_publicly_posted');
            }
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('published_to_portal');
            }
            if (!Schema::hasColumn('hr_vacancy_requisitions', 'public_slug')) {
                $table->string('public_slug', 255)->nullable()->unique()->after('published_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_vacancy_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('hr_vacancy_requisitions', 'published_to_portal')) {
                $table->dropColumn('published_to_portal');
            }
            if (Schema::hasColumn('hr_vacancy_requisitions', 'published_at')) {
                $table->dropColumn('published_at');
            }
            if (Schema::hasColumn('hr_vacancy_requisitions', 'public_slug')) {
                $table->dropColumn('public_slug');
            }
        });
    }
};
