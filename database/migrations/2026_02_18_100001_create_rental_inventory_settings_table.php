<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Settings for rental ↔ inventory integration per branch.
     * default_storage_location_id = store; out_on_rent_location_id = "Out on Rent".
     */
    public function up(): void
    {
        Schema::create('rental_inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('default_storage_location_id')->nullable()
                ->constrained('inventory_locations')->onDelete('set null');
            $table->foreignId('out_on_rent_location_id')->nullable()
                ->constrained('inventory_locations')->onDelete('set null');
            $table->timestamps();

            $table->unique(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_inventory_settings');
    }
};
