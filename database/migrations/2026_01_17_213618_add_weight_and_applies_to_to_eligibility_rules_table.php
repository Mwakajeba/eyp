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
        Schema::table('hr_eligibility_rules', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(0)->after('is_mandatory'); // Percentage weight for scoring
            $table->string('applies_to')->default('all')->after('weight'); // e.g., 'all', or a condition logic
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_eligibility_rules', function (Blueprint $table) {
            $table->dropColumn(['weight', 'applies_to']);
        });
    }
};
