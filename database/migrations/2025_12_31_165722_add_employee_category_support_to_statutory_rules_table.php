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
        Schema::table('hr_statutory_rules', function (Blueprint $table) {
            $table->boolean('apply_to_all_employees')->default(true)->after('is_active');
            $table->string('category_name')->nullable()->after('apply_to_all_employees');
            $table->text('category_description')->nullable()->after('category_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_statutory_rules', function (Blueprint $table) {
            $table->dropColumn(['apply_to_all_employees', 'category_name', 'category_description']);
        });
    }
};
