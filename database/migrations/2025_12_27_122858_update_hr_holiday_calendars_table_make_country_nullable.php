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
        Schema::table('hr_holiday_calendars', function (Blueprint $table) {
            // Make country nullable and remove default
            $table->string('country', 100)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_holiday_calendars', function (Blueprint $table) {
            // Restore default value
            $table->string('country', 100)->default('Tanzania')->change();
        });
    }
};
