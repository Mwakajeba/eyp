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
            $table->integer('sdl_min_employees')->nullable()->after('sdl_threshold')->comment('Minimum number of employees required for SDL (default: 10)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_statutory_rules', function (Blueprint $table) {
            $table->dropColumn('sdl_min_employees');
        });
    }
};
