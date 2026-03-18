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
        Schema::table('hr_employees', function (Blueprint $table) {
            // WCF percentage fields
            $table->decimal('wcf_employee_percent', 5, 2)->nullable()->after('has_wcf');
            $table->decimal('wcf_employer_percent', 5, 2)->nullable()->after('wcf_employee_percent');
            
            // HESLB percentage fields
            $table->decimal('heslb_employee_percent', 5, 2)->nullable()->after('has_heslb');
            $table->decimal('heslb_employer_percent', 5, 2)->nullable()->after('heslb_employee_percent');
            
            // SDL percentage fields
            $table->decimal('sdl_employee_percent', 5, 2)->nullable()->after('has_sdl');
            $table->decimal('sdl_employer_percent', 5, 2)->nullable()->after('sdl_employee_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn([
                'wcf_employee_percent',
                'wcf_employer_percent',
                'heslb_employee_percent',
                'heslb_employer_percent',
                'sdl_employee_percent',
                'sdl_employer_percent'
            ]);
        });
    }
};
