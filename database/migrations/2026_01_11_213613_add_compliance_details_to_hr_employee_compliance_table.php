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
        Schema::table('hr_employee_compliance', function (Blueprint $table) {
            $table->json('compliance_details')->nullable()->after('compliance_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_compliance', function (Blueprint $table) {
            $table->dropColumn('compliance_details');
        });
    }
};
