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
        Schema::table('hr_external_loans', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('institution_name');
            $table->enum('deduction_type', ['fixed', 'percentage'])->default('fixed')->after('monthly_deduction');
            $table->text('description')->nullable()->after('date_end_of_loan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_external_loans', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'deduction_type', 'description']);
        });
    }
};
