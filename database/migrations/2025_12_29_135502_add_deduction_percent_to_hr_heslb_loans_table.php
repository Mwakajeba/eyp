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
        Schema::table('hr_heslb_loans', function (Blueprint $table) {
            // Add deduction percentage field to HESLB loans
            // This allows each loan to have its own deduction percentage
            $table->decimal('deduction_percent', 5, 2)->nullable()->after('outstanding_balance')
                ->comment('Deduction percentage for this loan (e.g., 5.00 for 5%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_heslb_loans', function (Blueprint $table) {
            $table->dropColumn('deduction_percent');
        });
    }
};
