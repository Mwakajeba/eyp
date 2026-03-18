<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('donor_project_assignments', 'donor_id')) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donor_project_assignments' AND COLUMN_NAME = 'donor_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            );

            foreach ($foreignKeys as $foreignKey) {
                DB::statement('ALTER TABLE donor_project_assignments DROP FOREIGN KEY ' . $foreignKey->CONSTRAINT_NAME);
            }

            Schema::table('donor_project_assignments', function (Blueprint $table) {
                $table->dropColumn('donor_id');
            });
        }

        if (!Schema::hasColumn('donor_project_assignments', 'customer_id')) {
            Schema::table('donor_project_assignments', function (Blueprint $table) {
                $table->foreignId('customer_id')->after('project_id')->constrained('customers')->cascadeOnDelete();
                $table->unique(['project_id', 'customer_id']);
                $table->index(['company_id', 'customer_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('donor_project_assignments', 'customer_id')) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donor_project_assignments' AND COLUMN_NAME = 'customer_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            );

            foreach ($foreignKeys as $foreignKey) {
                DB::statement('ALTER TABLE donor_project_assignments DROP FOREIGN KEY ' . $foreignKey->CONSTRAINT_NAME);
            }

            Schema::table('donor_project_assignments', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'customer_id']);
                $table->dropUnique(['project_id', 'customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        if (!Schema::hasColumn('donor_project_assignments', 'donor_id')) {
            Schema::table('donor_project_assignments', function (Blueprint $table) {
                $table->foreignId('donor_id')->after('project_id')->constrained('donors')->cascadeOnDelete();
                $table->unique(['project_id', 'donor_id']);
            });
        }
    }
};
