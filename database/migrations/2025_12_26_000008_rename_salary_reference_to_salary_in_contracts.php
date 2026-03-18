<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('hr_contracts', 'salary_reference') && !Schema::hasColumn('hr_contracts', 'salary')) {
            // Use raw SQL for better MySQL compatibility
            DB::statement('ALTER TABLE `hr_contracts` CHANGE `salary_reference` `salary` DECIMAL(15,2) NULL');
        } elseif (!Schema::hasColumn('hr_contracts', 'salary')) {
            Schema::table('hr_contracts', function (Blueprint $table) {
                $table->decimal('salary', 15, 2)->nullable()->after('working_hours_per_week');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('hr_contracts', 'salary') && !Schema::hasColumn('hr_contracts', 'salary_reference')) {
            // Use raw SQL for better MySQL compatibility
            DB::statement('ALTER TABLE `hr_contracts` CHANGE `salary` `salary_reference` DECIMAL(15,2) NULL');
        }
    }
};

