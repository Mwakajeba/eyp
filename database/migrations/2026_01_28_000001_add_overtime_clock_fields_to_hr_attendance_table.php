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
        Schema::table('hr_attendance', function (Blueprint $table) {
            // Add overtime clock in/out fields after clock_out
            $table->time('overtime_clock_in')->nullable()->after('clock_out');
            $table->time('overtime_clock_out')->nullable()->after('overtime_clock_in');

            // Add break tracking fields
            $table->time('break_start')->nullable()->after('overtime_clock_out');
            $table->time('break_end')->nullable()->after('break_start');
            $table->integer('break_minutes')->default(0)->after('break_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_clock_in',
                'overtime_clock_out',
                'break_start',
                'break_end',
                'break_minutes',
            ]);
        });
    }
};
