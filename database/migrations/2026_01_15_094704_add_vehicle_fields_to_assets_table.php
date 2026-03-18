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
        Schema::table('assets', function (Blueprint $table) {
            // Fleet Management - Vehicle Specific Fields
            $table->string('registration_number')->nullable()->after('attachments');
            $table->enum('ownership_type', ['owned', 'leased', 'rented'])->nullable()->after('registration_number');
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'cng'])->nullable()->after('ownership_type');
            $table->decimal('capacity_tons', 8, 2)->nullable()->after('fuel_type');
            $table->decimal('capacity_volume', 10, 2)->nullable()->after('capacity_tons');
            $table->integer('capacity_passengers')->nullable()->after('capacity_volume');
            $table->date('license_expiry_date')->nullable()->after('capacity_passengers');
            $table->date('inspection_expiry_date')->nullable()->after('license_expiry_date');
            $table->enum('operational_status', ['available', 'assigned', 'in_repair', 'retired'])->default('available')->after('inspection_expiry_date');
            $table->string('gps_device_id')->nullable()->after('operational_status');
            $table->string('current_location')->nullable()->after('gps_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop Fleet Management - Vehicle Specific Fields
            $table->dropColumn([
                'registration_number',
                'ownership_type',
                'fuel_type',
                'capacity_tons',
                'capacity_volume',
                'capacity_passengers',
                'license_expiry_date',
                'inspection_expiry_date',
                'operational_status',
                'gps_device_id',
                'current_location',
            ]);
        });
    }
};
