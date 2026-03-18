<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_tyre_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('tyre_id')->constrained('fleet_tyres')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('tyre_position_id')->constrained('fleet_tyre_positions')->onDelete('restrict');
            $table->date('installed_at');
            $table->decimal('odometer_at_install', 12, 2)->nullable()->comment('Truck odometer at installation');
            $table->string('installer_type', 50)->nullable()->comment('garage, internal');
            $table->string('installer_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['company_id', 'vehicle_id']);
            $table->index(['tyre_id', 'installed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_tyre_installations');
    }
};
