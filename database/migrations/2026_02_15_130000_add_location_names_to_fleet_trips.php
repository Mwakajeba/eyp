<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Human-readable location names (from reverse geocoding).
     */
    public function up(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            $table->string('start_location_name', 500)->nullable()->after('start_longitude');
            $table->string('last_location_name', 500)->nullable()->after('last_location_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            $table->dropColumn(['start_location_name', 'last_location_name']);
        });
    }
};
