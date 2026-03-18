<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Start location = where driver was when they started the trip.
     * Last location = latest GPS update during the trip (live tracking).
     */
    public function up(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            $table->decimal('start_latitude', 10, 7)->nullable()->after('actual_start_date');
            $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
            $table->decimal('last_location_lat', 10, 7)->nullable()->after('start_longitude');
            $table->decimal('last_location_lng', 10, 7)->nullable()->after('last_location_lat');
            $table->timestamp('last_location_at')->nullable()->after('last_location_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            $table->dropColumn([
                'start_latitude',
                'start_longitude',
                'last_location_lat',
                'last_location_lng',
                'last_location_at',
            ]);
        });
    }
};
