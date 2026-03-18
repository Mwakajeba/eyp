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
        Schema::table('imprest_requests', function (Blueprint $table) {
            // Drop the old foreign key constraint that references departments table
            $table->dropForeign(['department_id']);
        });

        Schema::table('imprest_requests', function (Blueprint $table) {
            // Add new foreign key constraint that references hr_departments table
            $table->foreign('department_id')->references('id')->on('hr_departments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imprest_requests', function (Blueprint $table) {
            // Drop the hr_departments foreign key
            $table->dropForeign(['department_id']);
        });

        Schema::table('imprest_requests', function (Blueprint $table) {
            // Restore the original departments foreign key
            $table->foreign('department_id')->references('id')->on('departments');
        });
    }
};
