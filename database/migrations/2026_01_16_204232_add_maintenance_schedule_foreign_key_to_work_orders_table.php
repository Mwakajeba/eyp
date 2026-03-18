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
        Schema::table('fleet_maintenance_work_orders', function (Blueprint $table) {
            // Add foreign key constraint to maintenance_schedule_id
            // This runs after both tables are created
            $table->foreign('maintenance_schedule_id')
                ->references('id')
                ->on('fleet_maintenance_schedules')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_maintenance_work_orders', function (Blueprint $table) {
            $table->dropForeign(['maintenance_schedule_id']);
        });
    }
};
